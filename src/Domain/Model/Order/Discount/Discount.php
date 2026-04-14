<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Discount;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Cash\Percentage;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\ItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Promo\DiscountId as PromoDiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

final class Discount implements ChildEntity
{
    use HasData;

    public readonly OrderId $orderId;

    public readonly DiscountId $discountId;

    public readonly DiscountableType $discountableType;

    public readonly DiscountableId $discountableId;

    public readonly ?PromoId $promoId;

    public readonly ?PromoDiscountId $promoDiscountId;

    private readonly DiscountPrice|ItemDiscountPrice $discountPrice;

    private function __construct() {}

    /**
     * The discount amount (excluding VAT).
     */
    public function getDiscountPrice(): DiscountPrice|ItemDiscountPrice
    {
        return $this->discountPrice;
    }

    public function getPercentage(Money $basePrice): Percentage
    {
        return Cash::from($this->discountPrice->getExcludingVat())->asPercentage($basePrice);
    }

    public function getMappedData(): array
    {
        $data = $this->addDataIfNotNull([
            'promo_id' => $this->promoId?->get(),
            'promo_discount_id' => $this->promoDiscountId?->get(),
        ]);

        return [
            'order_id' => $this->orderId->get(),
            'discountable_type' => $this->discountableType->value,
            'discountable_id' => $this->discountableId->get(),
            'discount_id' => $this->discountId->get(),
            'promo_id' => $this->promoId?->get(),
            'promo_discount_id' => $this->promoDiscountId?->get(),
            'total_excl' => $this->discountPrice->getExcludingVat()->getAmount(),
            'total_incl' => $this->discountPrice instanceof ItemDiscountPrice && $this->discountPrice->isIncludingVatAuthoritative() ? $this->discountPrice->getIncludingVat()->getAmount() : null,
            'vat_rate' => $this->discountPrice instanceof ItemDiscountPrice ? $this->discountPrice->getVatPercentage()->get() : null,
            'data' => json_encode($data),
        ];
    }

    public static function create(OrderId $orderId, DiscountId $discountId, DiscountableType $discountableType, DiscountableId $discountableId, PromoId $promoId, PromoDiscountId $promoDiscountId, DiscountPrice|ItemDiscountPrice $discountPrice, array $data): static
    {
        $discount = new self;

        $discount->orderId = $orderId;
        $discount->discountId = $discountId;
        $discount->discountableType = $discountableType;
        $discount->discountableId = $discountableId;
        $discount->promoId = $promoId;
        $discount->promoDiscountId = $promoDiscountId;
        $discount->discountPrice = $discountPrice;
        $discount->data = $data;

        return $discount;
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $discount = new static;

        $discount->orderId = OrderId::fromString($aggregateState['order_id']);
        $discount->discountId = DiscountId::fromString($state['discount_id']);
        $discount->discountableType = DiscountableType::from($state['discountable_type']);
        $discount->discountableId = DiscountableId::fromString($state['discountable_id']);
        $discount->promoId = $state['promo_id'] ? PromoId::fromString($state['promo_id']) : null;
        $discount->promoDiscountId = $state['promo_discount_id'] ? PromoDiscountId::fromString($state['promo_discount_id']) : null;
        $discount->data = json_decode($state['data'], true);

        if ($discount->discountableType == DiscountableType::line) {

            if (! isset($state['vat_rate'])) {
                throw new \InvalidArgumentException('vat_rate is required for line discounts');
            }

            if (isset($state['total_incl']) && $state['total_incl'] !== null) {
                $discount->discountPrice = DefaultItemDiscountPrice::fromIncludingVat(
                    Money::EUR($state['total_incl']),
                    VatPercentage::fromString($state['vat_rate'])
                );
            } else {
                $discount->discountPrice = DefaultItemDiscountPrice::fromExcludingVat(
                    Money::EUR($state['total_excl']),
                    VatPercentage::fromString($state['vat_rate'])
                );
            }

        } else {
            $discount->discountPrice = DefaultDiscountPrice::fromExcludingVat(Money::EUR($state['total_excl']));
        }

        return $discount;
    }
}
