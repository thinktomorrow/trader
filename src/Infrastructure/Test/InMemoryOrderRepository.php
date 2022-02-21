<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test;

use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;

final class InMemoryOrderRepository implements OrderRepository
{
    private static array $orders = [];

    private string $nextReference = 'xxx-123';
    private string $nextShippingReference = 'shipping-123';

    public function save(Order $order): void
    {
        static::$orders[$order->orderId->get()] = $order;
    }

    public function find(OrderId $orderId): Order
    {
        if(!isset(static::$orders[$orderId->get()])) {
            throw new CouldNotFindOrder('No order found by id ' . $orderId);
        }

        return static::$orders[$orderId->get()];
    }

    public function delete(OrderId $orderId): void
    {
        if(!isset(static::$orders[$orderId->get()])) {
            throw new CouldNotFindOrder('No order found by id ' . $orderId);
        }

        unset(static::$orders[$orderId->get()]);
    }

    public function nextReference(): OrderId
    {
        return OrderId::fromString($this->nextReference);
    }

    public function nextShippingReference(): ShippingId
    {
        return ShippingId::fromString($this->nextShippingReference);
    }

    // For testing purposes only
    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public function setNextShippingReference(string $nextShippingReference): void
    {
        $this->nextShippingReference = $nextShippingReference;
    }

    public function clear()
    {
        static::$orders = [];
    }
}
