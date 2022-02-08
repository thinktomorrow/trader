<?php

namespace Optiphar\Cart\Adjusters\ItemGuards;

use Optiphar\Cart\Adjusters\Adjuster;
use Optiphar\Cart\Cart;

class AddedAsFreeItemGuard implements Adjuster
{
    public function adjust(Cart $cart)
    {
        // Remove the items that are added as free products within context of a discount
        $cart->items()->removeItemsAddedAsFreeItemDiscount();
    }
}
