<?php

namespace Optiphar\Cart\Adjusters;

use Optiphar\Cart\Cart;
use Optiphar\Cart\CartNote;
use Optiphar\Deliveries\Delivery;

class ShippingMethodAdjuster implements Adjuster
{
    public function adjust(Cart $cart)
    {
        if (! $cart->shipping()->hasMethod()) {
            return;
        }

        $shipping = Delivery::findByType($cart->shipping()->method(), false);

        if ($shipping->isAvailableForCart($cart)) {
            return;
        }

        $cart->addNote(CartNote::fromTranslations([
            'nl' => 'Verzendingswijze ontbreekt nog of is niet langer geldig.',
            'en' => 'Shipping method is missing or no longer valid.',
            'fr' => 'Shipping method is missing or no longer valid.',
        ])->tag('checkout')->toast());

        $adjustedCartShipping = $cart->shipping()->adjustMethod('');
        $cart->replaceShipping($adjustedCartShipping);
    }
}
