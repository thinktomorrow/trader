<?php

declare(strict_types = 1);

namespace Purchase\Cart\Application;

use Purchase\Cart\Ports\CartModel;
use Thinktomorrow\Trader\Purchase\Cart\CartState;
use Thinktomorrow\Trader\Purchase\Cart\CartReference;
use Thinktomorrow\Trader\Purchase\Cart\Events\CartRevived;
use function Thinktomorrow\Trader\Purchase\Cart\Application\event;

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
