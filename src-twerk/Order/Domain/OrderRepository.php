<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Domain;

use Illuminate\Support\Collection;

interface OrderRepository
{
    public function existsByReference(OrderReference $orderReference): bool;

    public function findByReference(OrderReference $orderReference): Order;

    public function emptyOrder(OrderReference $orderReference): Order;

    public function save(Order $order): void;

    public function delete(Order $order): void;

    public function nextReference(): OrderReference;

    public function filterByState(string ...$state): self;
    public function lastUpdatedBefore(\DateTime $threshold): self;
    public function get(): Collection;
}
