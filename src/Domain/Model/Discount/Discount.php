<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Discount;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;

final class Discount implements ChildEntity
{
    public readonly OrderId $orderId;
    public readonly DiscountId $discountId;
    private DiscountTotal $discountTotal;

    private function __construct(OrderId $orderId, DiscountId $discountId, DiscountTotal $discountTotal)
    {
        $this->orderId = $orderId;
        $this->discountId = $discountId;
        $this->discountTotal = $discountTotal;
    }

    public function getTotal(): DiscountTotal
    {
        return $this->discountTotal;
    }

    public function getMappedData(): array
    {
        return [
            'order_id' => $this->orderId->get(),
            'discount_id' => $this->discountId->get(),
            'total' => $this->discountTotal->getMoney()->getAmount(),
            'tax_rate' => $this->discountTotal->getTaxRate()->toPercentage()->get(),
            'includes_vat' => $this->discountTotal->includesTax(),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        return new static(
            OrderId::fromString($aggregateState['order_id']),
            DiscountId::fromString($state['discount_id']),
            DiscountTotal::fromScalars($state['total'], 'EUR', $state['tax_rate'], $state['includes_vat']),
        );
    }
}
