<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Discount;

use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Cash\Percentage;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Price\Price;
use Thinktomorrow\Trader\Domain\Common\Price\PriceTotal;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Promo\DiscountId as PromoDiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

final class Discount implements ChildEntity
{
    use HasData;

    public readonly OrderId $orderId;
    public readonly DiscountableType $discountableType;
    public readonly DiscountableId $discountableId;
    public readonly PromoId $promoId;
    public readonly DiscountId $discountId;
    public readonly PromoDiscountId $promoDiscountId;
    private readonly DiscountTotal $total;

    private function __construct()
    {
    }

    public function getTotal(): DiscountTotal
    {
        return $this->total;
    }

    public function getPercentage(Price|PriceTotal $price): Percentage
    {
        return Cash::from($this->total->getIncludingVat())->asPercentage($price->getIncludingVat());
    }

    public function getMappedData(): array
    {
        return [
            'order_id' => $this->orderId->get(),
            'discountable_type' => $this->discountableType->value,
            'discountable_id' => $this->discountableId->get(),
            'discount_id' => $this->discountId->get(),
            'promo_id' => $this->promoId->get(),
            'promo_discount_id' => $this->promoDiscountId->get(),
            'total' => $this->total->getIncludingVat()->getAmount(),
            'tax_rate' => $this->total->getTaxRate()->toPercentage()->get(),
            'includes_vat' => $this->total->includesVat(),
            'data' => json_encode($this->data),
        ];
    }

    public static function create(OrderId $orderId, DiscountId $discountId, DiscountableType $discountableType, DiscountableId $discountableId, PromoId $promoId, PromoDiscountId $promoDiscountId, DiscountTotal $discountTotal, array $data): static
    {
        $discount = new static();

        $discount->orderId = $orderId;
        $discount->discountId = $discountId;
        $discount->discountableType = $discountableType;
        $discount->discountableId = $discountableId;
        $discount->promoId = $promoId;
        $discount->promoDiscountId = $promoDiscountId;
        $discount->total = $discountTotal;
        $discount->data = $data;

        return $discount;
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $discount = new static();

        $discount->orderId = OrderId::fromString($aggregateState['order_id']);
        $discount->discountableType = DiscountableType::from($state['discountable_type']);
        $discount->discountableId = DiscountableId::fromString($state['discountable_id']);
        $discount->promoId = PromoId::fromString($state['promo_id']);
        $discount->discountId = DiscountId::fromString($state['discount_id']);
        $discount->promoDiscountId = PromoDiscountId::fromString($state['promo_discount_id']);
        $discount->total = DiscountTotal::fromScalars($state['total'], $state['tax_rate'], $state['includes_vat']);
        $discount->data = json_decode($state['data'], true);

        return $discount;
    }
}
