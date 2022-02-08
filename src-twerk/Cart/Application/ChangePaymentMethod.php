<?php

namespace Thinktomorrow\Trader\Cart\Application;

use Thinktomorrow\Trader\Purchase\Cart\Http\CurrentCart;

class ChangePaymentMethod
{
    /** @var CurrentCart */
    private $currentCart;

    public function __construct(CurrentCart $currentCart)
    {
        $this->currentCart = $currentCart;
    }

    public function handle(string $method)
    {
        $cart = $this->currentCart->get();

        $cart->replacePayment($cart->payment()->adjustMethod($method));

        $this->currentCart->save($cart);
    }
}
