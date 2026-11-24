<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts;

use Assert\Assertion;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Application\Promo\ApplicableConditions\ApplicableCondition;
use Thinktomorrow\Trader\Application\Promo\Preview\PromoPreviewProduct;
use Thinktomorrow\Trader\Application\Promo\Preview\PromoPreviewStatus;
use Thinktomorrow\Trader\Application\VatRate\Allocator\VatAllocator;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\ItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount\AppliedDiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount\DiscountableItem;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\PromoDiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

abstract class BaseApplicableDiscount
{
    protected VatAllocator $vatAllocator;

    protected readonly PromoId $promoId;

    protected readonly PromoDiscountId $promoDiscountId;

    protected array $promoData;

    protected DiscountableType $discountableType;

    /** @var ApplicableCondition[] */
    protected array $conditions;

    abstract public static function getMapKey(): string;

    protected static function fixedDiscountableType(): ?DiscountableType
    {
        return null;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function getDiscountableType(): DiscountableType
    {
        return $this->discountableType;
    }

    public function couldApply(Order $order): bool
    {
        // Missing lines, shipping or payment targets are immediately regarded as not applicable.
        // This allows us to avoid unnecessary evaluation logic on discounts that will
        // never be applicable in the first place.
        return match ($this->discountableType) {
            DiscountableType::line => $order->getLines() !== [],
            DiscountableType::shipping => $order->getShippings() !== [],
            DiscountableType::payment => $order->getPayments() !== [],
            DiscountableType::order => true,
        };
    }

    public function promoPreviewStatus(PromoPreviewProduct|Cart|CartShipping|CartPayment $subject, ?Cart $cart): PromoPreviewStatus
    {
        if (! $this->isPreviewRelevant($subject)) {
            return PromoPreviewStatus::Irrelevant;
        }

        $isPdp = $subject instanceof PromoPreviewProduct && $subject->quantityToAdd > 0;
        $status = PromoPreviewStatus::AlreadyEligible;

        if ($this->conditions === [] && $isPdp) {
            // An unconditional order discount is not an incentive for this product.
            $status = $this->discountableType === DiscountableType::order
                ? PromoPreviewStatus::Irrelevant
                : PromoPreviewStatus::Applicable;
        }

        foreach ($this->conditions as $condition) {
            $conditionStatus = $condition->evaluatePreview($cart, $subject);

            if ($conditionStatus === PromoPreviewStatus::Irrelevant) {
                return PromoPreviewStatus::Irrelevant;
            }

            if ($conditionStatus === PromoPreviewStatus::Conditional) {
                $status = PromoPreviewStatus::Conditional;
            } elseif ($conditionStatus === PromoPreviewStatus::Applicable && $status === PromoPreviewStatus::AlreadyEligible) {
                $status = PromoPreviewStatus::Applicable;
            }
        }

        if ($status === PromoPreviewStatus::Applicable
            && $this->promoData['coupon_code'] !== null
            && $cart?->getEnteredCoupon() !== $this->promoData['coupon_code']) {
            return PromoPreviewStatus::Conditional;
        }

        return $status;
    }

    /**
     * First filter for whether this suggestion subject can be relevant to the
     * discount. This does not check conditions or whether the discount is applied.
     *
     * Line discounts must override this to decide which products they concern;
     * SalePriceProductDiscount does so for products with a sale price.
     */
    protected function isPreviewRelevant(PromoPreviewProduct|Cart|CartShipping|CartPayment $subject): bool
    {
        return match ($this->discountableType) {
            DiscountableType::order => $subject instanceof PromoPreviewProduct || $subject instanceof Cart,
            // A line discount needs its own product-specific relevance check.
            DiscountableType::line => false,
            DiscountableType::shipping => $subject instanceof CartShipping,
            DiscountableType::payment => $subject instanceof CartPayment,
        };
    }

    protected function isApplicable(Order $order, DiscountableItem $discountable): bool
    {
        foreach ($this->conditions as $condition) {
            if (! $condition->check($order, $discountable)) {
                return false;
            }
        }

        return true;
    }

    abstract public function getDiscountPrice(Order $order, DiscountableItem $discountable): DiscountPrice|ItemDiscountPrice;

    public function apply(Order $order, DiscountableItem $discountable, AppliedDiscountId $nextDiscountId): void
    {
        if (! $this->isApplicable($order, $discountable)) {
            return;
        }

        $price = $this->getDiscountPrice($order, $discountable);

        if (($discountable instanceof Shipping || $discountable instanceof Payment) && $price instanceof DiscountPrice) {
            $remaining = $discountable instanceof Shipping ? $discountable->getShippingCostTotal() : $discountable->getPaymentCostTotal();
            if ($remaining->getExcludingVat()->isZero()) {
                return;
            }

            $exceedsExcludingVat = $price->getExcludingVat()->greaterThan($remaining->getExcludingVat());
            $exceedsIncludingVat = $price->getTaxMode() === TaxMode::Inclusive
                && $remaining->getTaxMode() === TaxMode::Inclusive
                && $price->getAuthoritativeAmount()->greaterThan($remaining->getAuthoritativeAmount());

            if ($exceedsExcludingVat || $exceedsIncludingVat) {
                if ($price->getTaxMode() === TaxMode::Inclusive && $remaining->getTaxMode() === TaxMode::Inclusive) {
                    $price = DefaultDiscountPrice::fromIncludingVat(
                        $exceedsIncludingVat ? $remaining->getAuthoritativeAmount() : $price->getAuthoritativeAmount(),
                        $exceedsExcludingVat ? $remaining->getExcludingVat() : $price->getExcludingVat(),
                    );
                } else {
                    $price = DefaultDiscountPrice::fromExcludingVat($remaining->getExcludingVat());
                }
            }
        }

        $discount = Discount::create(
            $order->orderId,
            $nextDiscountId,
            $discountable->getDiscountableType(),
            $discountable->getDiscountableId(),
            $this->promoId,
            $this->promoDiscountId,
            $price,
            $this->promoData,
        );

        $discountable->addDiscount($discount);
    }

    public static function fromMappedData(array $state, array $aggregateState, array $conditions, VatAllocator $vatAllocator): static
    {
        Assertion::allIsInstanceOf($conditions, ApplicableCondition::class);

        $discount = new static;
        $discount->promoId = PromoId::fromString($aggregateState['promo_id']);
        $discount->promoDiscountId = PromoDiscountId::fromString($state['discount_id']);
        $discount->discountableType = static::resolveDiscountableType($state);
        $discount->promoData = [
            'coupon_code' => $aggregateState['coupon_code'],
            ...json_decode($aggregateState['data'], true),
        ];
        $discount->conditions = $conditions;
        $discount->vatAllocator = $vatAllocator;

        return $discount;
    }

    protected static function resolveDiscountableType(array $state): DiscountableType
    {
        $fixedType = static::fixedDiscountableType();

        if (! isset($state['discountable_type']) && $fixedType === null) {
            throw new \InvalidArgumentException('Specify discountable_type for discount ['.static::getMapKey().']. Important for line, shipping and payment discounts.');
        }

        $type = isset($state['discountable_type'])
            ? DiscountableType::from($state['discountable_type'])
            : $fixedType;

        if ($fixedType !== null && $type !== $fixedType) {
            throw new \InvalidArgumentException('Invalid discountable_type for built-in discount ['.static::getMapKey().'].');
        }

        return $type;
    }
}
