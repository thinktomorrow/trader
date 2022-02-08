<?php

declare(strict_types=1);

namespace Purchase\Cart\Domain;

interface CurrentCartSource
{
    public function exists(): bool;

    public function get(): Cart;

    public function getReference(): ?CartReference;

    public function setReference(CartReference $cartReference): self;

    public function set(Cart $cart): self;

    public function forget(): void;
}
