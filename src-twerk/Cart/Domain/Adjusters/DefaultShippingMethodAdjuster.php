<?php

namespace Thinktomorrow\Trader\Purchase\Cart\Adjusters;

use Optiphar\Checkout\ApplicableShippingMethods;
use Optiphar\Payments\PaymentStatus;
use Optiphar\Users\Customers\Customer;
use Thinktomorrow\Trader\Purchase\Cart\Cart;

class DefaultShippingMethodAdjuster implements Adjuster
{
    /** @var ApplicableShippingMethods */
    private $applicableShippingMethods;

    public function __construct(ApplicableShippingMethods $applicableShippingMethods)
    {
        $this->applicableShippingMethods = $applicableShippingMethods;
    }

    public function adjust(Cart $cart)
    {
        if ($cart->shipping()->hasMethod()) {
            return;
        }

        foreach (['default', 'firstApplicable', 'fromCustomer'] as $setter) {
            $this->$setter($cart);
        }
    }

    /**
     * By default we set the first applicable shipping method for the selected country.
     * If none applicable, the default is taken from the config.
     *
     * @param Cart $cart
     * @return Cart
     */
    private function default(Cart $cart)
    {
        if (! $method = config('optiphar.cart-defaults.method')) {
            return;
        }

        $this->replaceShippingMethod($cart, $method);
    }

    /**
     * By default we set the first applicable shipping method for the selected country.
     * If none applicable, the default is taken from the config.
     *
     * @param Cart $cart
     */
    private function firstApplicable(Cart $cart)
    {
        $applicableShippingMethods = $this->applicableShippingMethods->fromCart($cart);

        if ($applicableShippingMethods->isEmpty()) {
            return;
        }

        // If default is set and is one of the applicable methods, we'll keep it.
        if ($cart->shipping()->hasMethod() && $applicableShippingMethods->contains(function ($shippingMethod) use ($cart) {
            return $shippingMethod->code == $cart->shipping()->method();
        })) {
            return;
        }

        $this->replaceShippingMethod($cart, $applicableShippingMethods->first()->code);
    }

    private function fromCustomer(Cart $cart)
    {
        if (! $customer = Customer::find($cart->customer()->customerId())) {
            return;
        }

        if (! $lastOrder = $customer->orders->where('statuspayment', PaymentStatus::RECEIVED)->sortBy('mtime')->last()) {
            return;
        }

        $this->replaceShippingMethod($cart, $lastOrder->delivery()->first()->code);
    }

    private function replaceShippingMethod(Cart $cart, string $method)
    {
        $adjustedCartShipping = $cart->shipping()->adjustMethod($method);

        $cart->replaceShipping($adjustedCartShipping);
    }
}
