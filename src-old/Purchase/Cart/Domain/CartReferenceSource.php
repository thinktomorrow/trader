<?php

namespace Purchase\Cart\Domain;

interface CartReferenceSource
{
    public function exists(): bool;

    public function get(): CartReference;

    public function set(CartReference $cartReference): void;

    public function forget(): void;
}
