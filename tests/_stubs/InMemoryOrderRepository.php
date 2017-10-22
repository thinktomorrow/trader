<?php

namespace Thinktomorrow\Trader\Tests\Stubs;

use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Orders\Domain\Exceptions\OrderNotFound;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Orders\Domain\OrderReference;
use Thinktomorrow\Trader\Orders\Domain\OrderRepository;

class InMemoryOrderRepository implements OrderRepository
{
    private static $collection = [];

    public function find(OrderId $orderId): Order
    {
        if (isset(self::$collection[(string) $orderId])) {
            return self::$collection[(string) $orderId];
        }

        throw new OrderNotFound('No order found by id ['.$orderId->get().']');
    }

    public function findOrCreate(OrderId $orderId): Order
    {
        if (isset(self::$collection[(string) $orderId])) {
            return self::$collection[(string) $orderId];
        }

        $order = new Order($this->nextIdentity());
        $this->add($order);

        return $order;
    }

    public function add(Order $order)
    {
        self::$collection[(string) $order->id()] = $order;
    }

    public function remove(OrderId $orderId)
    {
        unset(self::$collection[(string) $orderId->get()]);
    }

    public function nextIdentity() : OrderId
    {
        return OrderId::fromString((string) Uuid::uuid4());
    }

    public function nextReference(): OrderReference
    {
        return OrderReference::fromString((string) Uuid::uuid4());
    }
}
