<?php

namespace Thinktomorrow\Trader\Purchase\Cart\Events;

use Optiphar\Cart\CartReference;

class CartCommitted
{
    /** @var CartReference */
    public $cartReference;

    public function __construct(CartReference $cartReference)
    {
        $this->cartReference = $cartReference;
    }
}
