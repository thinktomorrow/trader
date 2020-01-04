<?php

declare(strict_types = 1);

namespace Thinktomorrow\Trader\Purchase\Cart\Application;

use Thinktomorrow\Trader\Purchase\Cart\CartReference;
use Thinktomorrow\Trader\Purchase\Cart\CartState;
use Thinktomorrow\Trader\Purchase\Cart\Events\CartAbandoned;
use Thinktomorrow\Trader\Purchase\Cart\Ports\CartModel;

class AbandonCart
{
    public function handle(CartReference $cartReference)
    {
        CartModel::findByReference($cartReference)->update(['state' => CartState::ABANDONED]);

        event(new CartAbandoned($cartReference));

        return;
    }
}
