<?php

namespace Optiphar\Cart\Adjusters;

use Optiphar\Cart\Cart;
use Optiphar\Cart\CartNote;
use Optiphar\Payments\Products\Payment;

class PaymentMethodAdjuster implements Adjuster
{
    public function adjust(Cart $cart)
    {
        if(!$cart->payment()->hasMethod()) return;

        $payment = Payment::findByType($cart->payment()->method(), false);

        if($payment->isAvailableForCart($cart)) return;

        $cart->addNote(CartNote::fromTranslations([
            'nl' => 'Betalingswijze ontbreekt nog of is niet langer geldig.',
            'en' => 'Payment method is missing or invalid.',
            'fr' => 'Payment method is missing or invalid.',
        ])->tag('checkout')->toast());

        $adjustedCartPayment = $cart->payment()->adjustMethod('');
        $cart->replacePayment($adjustedCartPayment);
    }
}
