<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;

interface OrderRepository
{
    public function save(Order $order): void;

    public function find(OrderId $orderId): Order;

    public function delete(OrderId $orderId): void;

    public function nextReference(): OrderId;

    public function nextShippingReference(): ShippingId;
}