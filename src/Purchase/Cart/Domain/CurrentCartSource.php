<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Cart\Domain;

interface CurrentCartSource
{
    public function exists(): bool;

    public function get(): Cart;

    public function getReference(): ?CartReference;

    public function setReference(CartReference $cartReference): void;

    public function set(Cart $cart): void;

    public function forget(): void;
}
