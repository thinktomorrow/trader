<?php

namespace Optiphar\Cart\Events;

use Optiphar\Cart\CartReference;

class CartDeleted
{
    /** @var CartReference */
    public $cartReference;

    public function __construct(CartReference $cartReference)
    {
        $this->cartReference = $cartReference;
    }
}
