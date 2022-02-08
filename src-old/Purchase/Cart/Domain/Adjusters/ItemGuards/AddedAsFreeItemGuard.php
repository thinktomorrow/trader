<?php

namespace Purchase\Cart\Domain\Adjusters\ItemGuards;

use Optiphar\Cart\Cart;
use Optiphar\Cart\Adjusters\Adjuster;

class AddedAsFreeItemGuard implements Adjuster
{
    public function adjust(Cart $cart)
    {
        // Remove the items that are added as free products within context of a discount
        $cart->items()->removeItemsAddedAsFreeItemDiscount();
    }
}
