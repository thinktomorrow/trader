<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Cart\Domain;

use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Order\Domain\OrderReference;

interface CartRepository
{
    public function existsByReference(OrderReference $orderReference): bool;

    public function findByReference(OrderReference $orderReference): Order;

    public function save(Order $order): void;

    public function nextReference(): OrderReference;

    public function emptyCart(OrderReference $orderReference): Order;
}
