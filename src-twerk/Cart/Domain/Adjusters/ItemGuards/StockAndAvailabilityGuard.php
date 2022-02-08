<?php

namespace Thinktomorrow\Trader\Purchase\Cart\Adjusters\ItemGuards;

use Optiphar\Cart\CartNote;
use Thinktomorrow\Trader\Purchase\Cart\Adjusters\Adjuster;
use Thinktomorrow\Trader\Purchase\Cart\Cart;

class StockAndAvailabilityGuard implements Adjuster
{
    public function adjust(Cart $cart)
    {
        foreach ($cart->items() as $item) {
            if (! $item->isAvailable()) {
                $cart->addNote(CartNote::fromTransKey('basket.notadded.unavailable', [
                    'name' => $item->label(),
                ])->tag('add_to_cart', 'cart')->red());

                $cart->items()->remove($item->id());
            } elseif (! $item->inStock()) {
                $cart->addNote(CartNote::fromTransKey('basket.notadded.stock', [
                    'name' => $item->label(),
                ])->tag('add_to_cart', 'cart')->red());

                $cart->items()->remove($item->id());
            } elseif (! $item->hasValidPrice()) {
                $cart->addNote(CartNote::fromTransKey('basket.notadded.price', [
                    'name' => $item->label(),
                ])->tag('add_to_cart', 'cart')->red());

                $cart->items()->remove($item->id());
            } elseif (! $item->isVisible()) {
                $cart->addNote(CartNote::fromTransKey('basket.notadded.unavailable', [
                    'name' => $item->label(),
                ])->tag('add_to_cart', 'cart')->red());

                $cart->items()->remove($item->id());
            }
        }
    }
}
