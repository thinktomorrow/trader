<?php

namespace Purchase\Cart\Domain\Adjusters;

use Optiphar\Users\Customers\Customer;
use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Thinktomorrow\Trader\Purchase\Cart\Adjusters\Adjuster;
use function Thinktomorrow\Trader\Purchase\Cart\Adjusters\config;

class DefaultShippingCountryAdjuster implements Adjuster
{
    public function adjust(Cart $cart)
    {
        if($cart->shipping()->hasCountry()) return;

        foreach (['default','fromCustomer'] as $setter) {
            $this->$setter($cart);
        }
    }

    private function default(Cart $cart)
    {
        return $this->replaceShippingCountry($cart, config('optiphar.cart-defaults.country'));
    }

    private function fromCustomer(Cart $cart)
    {
        if(!$customer = Customer::find($cart->customer()->customerId())) return;

        if($deliveryAddress = $customer->getLastDeliveryAddress()){
            $this->replaceShippingCountry($cart, $deliveryAddress->countryid);
        }
    }

    private function replaceShippingCountry(Cart $cart, ?string $countryid): void
    {
        $address = $cart->shipping()->toArray()['address'];

        $adjustedCartShipping = $cart->shipping()->adjustAddress(array_merge($address, [
            'countryid' => $countryid
        ]));

        $cart->replaceShipping($adjustedCartShipping);
    }

}
