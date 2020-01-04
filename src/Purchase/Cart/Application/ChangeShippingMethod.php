<?php

namespace Optiphar\Cart\Application;

use Optiphar\Cart\Http\DbCurrentCartSource;

class ChangeShippingMethod
{
    /** @var DbCurrentCartSource */
    private $currentCart;

    public function __construct(DbCurrentCartSource $currentCart)
    {
        $this->currentCart = $currentCart;
    }

    public function handle(string $method)
    {
        $cart = $this->currentCart->get();

        $cart->replaceShipping($cart->shipping()->adjustMethod($method));

        $this->currentCart->save($cart);
    }
}
