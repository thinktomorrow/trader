<?php

namespace Thinktomorrow\Trader\Cart\Application;

use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Thinktomorrow\Trader\Purchase\Cart\CartReference;
use Thinktomorrow\Trader\Purchase\Cart\CartState;
use Thinktomorrow\Trader\Purchase\Cart\Events\CartConfirmed;
use Thinktomorrow\Trader\Purchase\Cart\Ports\CartModel;

class ConfirmCart
{
    /**
     * Save cart as order in database. If the order already exists
     * The order will be updated with the new cart data
     *
     * @param CartReference $cartReference
     */
    public function handle(CartReference $cartReference)
    {
        CartModel::findByReference($cartReference)->update(['state' => CartState::CONFIRMED]);

        event(new CartConfirmed($cartReference));
    }
}
