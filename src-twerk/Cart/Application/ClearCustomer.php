<?php

namespace Thinktomorrow\Trader\Cart\Application;

use Optiphar\Checkout\StepTracker;
use Thinktomorrow\Trader\Purchase\Cart\CartCustomer;
use Thinktomorrow\Trader\Purchase\Cart\CartPayment;
use Thinktomorrow\Trader\Purchase\Cart\CartShipping;
use Thinktomorrow\Trader\Purchase\Cart\Http\CurrentCart;

class ClearCustomer
{
    /** @var CurrentCart */
    private $currentCart;

    public function __construct(CurrentCart $currentCart)
    {
        $this->currentCart = $currentCart;
    }

    public function handle()
    {
        $cart = $this->currentCart->get();

        if (! $cart->customer()->exists()) {
            return;
        }

        // Clear payment, shipping info and customer
        $cart->replacePayment(CartPayment::empty());
        $cart->replaceShipping(CartShipping::empty());
        $cart->replaceCustomer(CartCustomer::empty());

        $this->currentCart->save($cart);

        // Also clear the checkout steps to prevent errors in checkout after logging back in
        app(StepTracker::class)->clear();
    }
}
