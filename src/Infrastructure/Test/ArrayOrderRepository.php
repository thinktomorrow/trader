<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test;

use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;

final class ArrayOrderRepository implements OrderRepository
{
    private array $orders = [];

    private string $nextReference = 'xxx-123';

    public function save(Order $order): void
    {
        $this->orders[$order->orderId->get()] = $order;
    }

    public function find(OrderId $orderId): Order
    {
        if(!isset($this->orders[$orderId->get()])) {
            throw new CouldNotFindOrder('No order found by id ' . $orderId);
        }

        return $this->orders[$orderId->get()];
    }

    public function delete(OrderId $orderId): void
    {
        if(!isset($this->orders[$orderId->get()])) {
            throw new CouldNotFindOrder('No order found by id ' . $orderId);
        }

        unset($this->orders[$orderId->get()]);
    }

    public function nextReference(): OrderId
    {
        return OrderId::fromString($this->nextReference);
    }

    // For testing purposes only
    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }
}
