<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Entity;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

interface OrderRepository
{
    public function save(Order $order): void;

    public function find(OrderId $orderId): Order;

    public function delete(OrderId $orderId): void;

    public function nextReference(): OrderId;
}
