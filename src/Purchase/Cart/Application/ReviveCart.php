<?php

declare(strict_types = 1);

namespace Thinktomorrow\Trader\Purchase\Cart\Application;

use Thinktomorrow\Trader\Purchase\Cart\CartReference;
use Thinktomorrow\Trader\Purchase\Cart\CartState;
use Thinktomorrow\Trader\Purchase\Cart\Events\CartRevived;
use Thinktomorrow\Trader\Purchase\Cart\Ports\CartModel;

class ReviveCart
{
    /**
     * Explicitly keep an abandoned cart and revive it
     * @param CartReference $cartReference
     */
    public function handle(CartReference $cartReference)
    {
        CartModel::findByReference($cartReference)->update(['state' => CartState::REVIVED]);

        event(new CartRevived($cartReference));

        return;
    }
}
