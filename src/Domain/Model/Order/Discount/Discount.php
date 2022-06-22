<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Discount;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceTotal;
use Thinktomorrow\Trader\Domain\Common\Cash\Percentage;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

final class Discount implements ChildEntity
{
    use HasData;

    public readonly OrderId $orderId;
    public readonly DiscountId $discountId;
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
            'discount_id' => $this->discountId->get(),
            'total' => $this->total->getIncludingVat()->getAmount(),
            'tax_rate' => $this->total->getTaxRate()->toPercentage()->get(),
            'includes_vat' => $this->total->includesVat(),
            'data' => json_encode($this->data),
        ];
    }

    public static function fromApplicableDiscount(OrderId $orderId, PromoId $promoId, DiscountId $discountId, DiscountTotal $discountTotal, array $data): static
    {
        $discount = new static();

        $discount->orderId = $orderId;
        $discount->promoId = $promoId;
        $discount->discountId = $discountId;
        $discount->total = $discountTotal;
        $discount->data = $data;

        return $discount;
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $discount = new static();

        $discount->orderId = OrderId::fromString($aggregateState['order_id']);
        $discount->discountId = DiscountId::fromString($state['discount_id']);
        $discount->total = DiscountTotal::fromScalars($state['total'], $state['tax_rate'], $state['includes_vat']);
        $discount->data = json_decode($state['data'], true);

        return $discount;
    }
}
