<?php

namespace Thinktomorrow\Trader\Orders\Domain\Read\CartItem;

use Thinktomorrow\Trader\Orders\Domain\Purchasable;

interface CartItem
{
    public function purchasable(): Purchasable;

    public function purchasableId(): int;

    public function purchasableType(): string;

    public function quantity(): int;

    public function price(): string;

    public function saleprice(): string;

    public function subtotal(): string;

    public function total(): string;

    public function taxRate(): string;
}
