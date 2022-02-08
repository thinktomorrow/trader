<?php

namespace Purchase\Cart\Domain\Events;

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
