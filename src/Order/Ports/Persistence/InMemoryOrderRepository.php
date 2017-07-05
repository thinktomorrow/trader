<?php

namespace Thinktomorrow\Trader\Order\Ports\Persistence;

use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Order\Domain\OrderId;
use Thinktomorrow\Trader\Order\Domain\OrderRepository;

class InMemoryOrderRepository implements OrderRepository
{
    private static $collection = [];

    public function find(OrderId $orderId): Order
    {
        if(isset(self::$collection[(string)$orderId])) return self::$collection[(string)$orderId];

        throw new \RuntimeException('Order not found by id ['.$orderId->get().']');
    }

    public function add(Order $order)
    {
        self::$collection[(string)$order->id()] = $order;
    }
}