<?php

namespace Thinktomorrow\Trader\Order\Domain;

interface OrderReferenceSource
{
    public function exists(): bool;

    public function get(): OrderReference;

    public function set(OrderReference $cartReference): void;

    public function forget(): void;
}
