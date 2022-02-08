<?php

namespace Purchase\Cart\Application;

use Purchase\Cart\Ports\CartModel;
use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Purchase\Cart\Domain\Events\CartConfirmed;
use Thinktomorrow\Trader\Purchase\Cart\CartState;
use Optiphar\Orders\Application\ConvertCartToOrder;
use Thinktomorrow\Trader\Purchase\Cart\CartReference;
use function Thinktomorrow\Trader\Purchase\Cart\Application\event;

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
