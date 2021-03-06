<?php

namespace Thinktomorrow\Trader\Orders\Domain;

interface OrderRepository
{
    /**
     * @param OrderId $orderId
     *
     * @throws \RuntimeException
     *
     * @return Order
     */
    public function find(OrderId $orderId): Order;

    public function findOrCreate(OrderId $orderId): Order;

    public function add(Order $order);

    public function remove(OrderId $orderId);

    public function nextIdentity(): OrderId;
}
