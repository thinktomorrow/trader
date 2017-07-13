<?php

namespace Thinktomorrow\Trader\Order\Domain;

interface OrderRepository
{
    public function find(OrderId $orderId): Order;

    public function add(Order $order);

    public function getValuesForMerchantOrder(OrderId $orderId): array;
}