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
    private Money $discountTotal;

    public function __construct(OrderId $orderId, Money $discountTotal, array $data)
    {
        $this->orderId = $orderId;
        $this->discountTotal = $discountTotal;
        $this->data = $data;
    }

    public function getTotal(): Money
    {
        return $this->discountTotal;
    }

    public function getMappedData(): array
    {
        return [
            'order_id' => $this->orderId->get(),
            'total' => $this->discountTotal->getAmount(),
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        return new static(
            OrderId::fromString($aggregateState['order_id']),
            Cash::make($state['total']),
            json_decode($state['data'], true),
        );
    }
}
