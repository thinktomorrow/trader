<?php

namespace Thinktomorrow\Trader\Orders\Domain;

interface OrderRepository
{
    /**
     * @param OrderId $orderId
     * @return null|Order
     */
    public function find(OrderId $orderId);

    public function add(Order $order);

    public function remove(OrderId $orderId);

    public function getValues(OrderId $orderId): array;

    public function getItemValues(OrderId $orderId): array;
}
