<?php

namespace Purchase\Cart\Domain\Events;

use Thinktomorrow\Trader\Purchase\Cart\CartReference;

class CartConfirmed
{
    /** @var CartReference */
    public $cartReference;

    public function __construct(CartReference $cartReference)
    {
        $this->cartReference = $cartReference;
    }
}