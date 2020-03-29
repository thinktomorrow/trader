<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Cart\Domain;

use Illuminate\Support\Collection;

interface CartRepository
{
    public function existsByReference(CartReference $cartReference): bool;

    public function findByReference(CartReference $cartReference): Cart;

    public function filterByState(string ...$state): self;

    public function lastUpdatedBefore(\DateTime $threshold): self;

    public function get(): Collection;

    public function save(Cart $cart): void;

    public function nextReference(): CartReference;

    public function emptyCart(CartReference $cartReference): Cart;
}
