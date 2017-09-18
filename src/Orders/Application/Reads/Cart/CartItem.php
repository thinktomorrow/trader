<?php

namespace Thinktomorrow\Trader\Orders\Application\Reads\Cart;

interface CartItem
{
    public function quantity(): int;

    public function price(): string;

    public function saleprice(): string;

    public function subtotal(): string;

    public function total(): string;

    public function taxRate(): string;
}
