<?php

namespace Thinktomorrow\Trader\Order\Domain;

interface OrderRepository
{
    public function find(OrderId $orderId): Order;

    public function add(Order $order);

    public function getValues(OrderId $orderId): array;

    public function getItemValues(OrderId $orderId): array;
}