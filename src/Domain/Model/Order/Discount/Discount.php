<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Discount;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

final class Discount implements ChildEntity
{
    use HasData;

    public readonly OrderId $orderId;
    public readonly DiscountId $discountId;
    private readonly Money $total;

    private function __construct(){}

    public function getTotal(): Money
    {
        return $this->total;
    }

    public function getMappedData(): array
    {
        return [
            'order_id' => $this->orderId->get(),
            'discount_id' => $this->discountId->get(),
            'total' => $this->total->getAmount(),
            'tax_rate' => DiscountTotal::getDiscountTaxRate()->toPercentage()->get(),
            'includes_vat' => true,
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $discount = new static();

        $discount->orderId = OrderId::fromString($aggregateState['order_id']);
        $discount->discountId = DiscountId::fromString($state['discount_id']);
        $discount->total = Cash::make($state['total']);
        $discount->data = json_decode($state['data'], true);

        return $discount;
    }
}
