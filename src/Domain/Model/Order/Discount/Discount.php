<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Discount;

use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Cash\Percentage;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\ExtractPriceExcludingVat;
use Thinktomorrow\Trader\Domain\Common\Price\PriceWithVat;
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
    private readonly DiscountPrice $discountPrice;

    private function __construct()
    {
    }

    /**
     * The discount amount excluding VAT.
     */
    public function getDiscountPrice(): DiscountPrice
    {
        return $this->discountPrice;
    }

    public function getPercentage(PriceWithVat $price): Percentage
    {
        return Cash::from($this->discountPrice->getExcludingVat())->asPercentage($price->getExcludingVat());
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
            'total' => $this->discountPrice->getExcludingVat()->getAmount(),
            'data' => json_encode($data),

            // Both these fields are no longer used but are kept for backward compatibility
            'tax_rate' => 0,
            'includes_vat' => false,
        ];
    }

    public static function create(OrderId $orderId, DiscountId $discountId, DiscountableType $discountableType, DiscountableId $discountableId, PromoId $promoId, PromoDiscountId $promoDiscountId, DiscountPrice $discountPrice, array $data): static
    {
        $discount = new static();

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
        $discount = new static();

        $amountExcludingVat = ExtractPriceExcludingVat::extract($state, 'total');

        $discount->orderId = OrderId::fromString($aggregateState['order_id']);
        $discount->discountId = DiscountId::fromString($state['discount_id']);
        $discount->discountableType = DiscountableType::from($state['discountable_type']);
        $discount->discountableId = DiscountableId::fromString($state['discountable_id']);
        $discount->promoId = $state['promo_id'] ? PromoId::fromString($state['promo_id']) : null;
        $discount->promoDiscountId = $state['promo_discount_id'] ? PromoDiscountId::fromString($state['promo_discount_id']) : null;
        $discount->discountPrice = DefaultDiscountPrice::fromExcludingVat($amountExcludingVat);
        $discount->data = json_decode($state['data'], true);

        return $discount;
    }
}
