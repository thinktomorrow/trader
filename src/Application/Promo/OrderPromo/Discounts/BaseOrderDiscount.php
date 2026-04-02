<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\OrderPromo\Discounts;

use Assert\Assertion;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderCondition;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\ItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableItem;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\DiscountId as PromoDiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

abstract class BaseOrderDiscount
{
    protected readonly PromoId $promoId;

    protected readonly PromoDiscountId $promoDiscountId;

    protected array $promoData;

    /** @var OrderCondition[] */
    protected array $conditions;

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

    public function apply(Order $order, DiscountableItem $discountable, DiscountId $nextDiscountId): void
    {
        if (! $this->isApplicable($order, $discountable)) {
            return;
        }

        $discount = Discount::create(
            $order->orderId,
            $nextDiscountId,
            $discountable->getDiscountableType(),
            $discountable->getDiscountableId(),
            $this->promoId,
            $this->promoDiscountId,
            $this->getDiscountPrice($order, $discountable),
            $this->promoData,
        );

        $discountable->addDiscount($discount);
    }

    public static function fromMappedData(array $state, array $aggregateState, array $conditions): static
    {
        Assertion::allIsInstanceOf($conditions, OrderCondition::class);

        $discount = new static;
        $discount->promoId = PromoId::fromString($aggregateState['promo_id']);
        $discount->promoDiscountId = PromoDiscountId::fromString($state['discount_id']);
        $discount->promoData = [
            'coupon_code' => $aggregateState['coupon_code'],
            ...json_decode($aggregateState['data'], true),
        ];
        $discount->conditions = $conditions;

        return $discount;
    }

    public function getCombinedDiscountPrice(Order $order): DiscountPrice
    {
        $discountPrice = $this->getDiscountPrice($order, $order);

        if ($discountPrice instanceof ItemDiscountPrice) {
            return DefaultDiscountPrice::fromExcludingVat($discountPrice->getExcludingVat());
        }

        return $discountPrice;
    }
}
