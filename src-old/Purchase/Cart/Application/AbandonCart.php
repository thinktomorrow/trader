<?php

declare(strict_types = 1);

namespace Purchase\Cart\Application;

use Purchase\Cart\Ports\CartModel;
use Thinktomorrow\Trader\Purchase\Cart\CartState;
use Thinktomorrow\Trader\Purchase\Cart\CartReference;
use Thinktomorrow\Trader\Purchase\Cart\Events\CartAbandoned;
use function Thinktomorrow\Trader\Purchase\Cart\Application\event;

class AbandonCart
{
    public function handle(CartReference $cartReference)
    {
        CartModel::findByReference($cartReference)->update(['state' => CartState::ABANDONED]);

        event(new CartAbandoned($cartReference));

        return;
    }
}
