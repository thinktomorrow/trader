<?php

namespace Thinktomorrow\Trader\Purchase\Cart\Adjusters;

use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Thinktomorrow\Trader\Purchase\Cart\CartPayment;
use Thinktomorrow\Trader\Purchase\Cart\CartCustomer;
use Thinktomorrow\Trader\Purchase\Cart\CartShipping;
use Illuminate\Auth\AuthManager;
use Optiphar\Users\OneTimeCustomers\OneTimeCustomerAuth;

class CustomerAdjuster implements Adjuster
{
    /** @var AuthManager */
    private $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    public function adjust(Cart $cart)
    {
        // Set current locale for cart
        $cart->replaceData('details.locale', app()->getLocale());

        if( ! $this->isCustomerLoggedIn() ) {
            $this->removeCustomerData($cart);
            return;
        }

        $this->fillCartWithCustomerData($cart);
    }

    private function isCustomerLoggedIn(): bool
    {
        return ($this->authManager->guard('customer')->check() || $this->authManager->guard('onetime_customer')->check());
    }

    /**
     * @param Cart $cart
     */
    private function fillCartWithCustomerData(Cart $cart): void
    {
        if(!$user = $this->authManager->guard('customer')->user()) {
            $user = OneTimeCustomerAuth::get()->toUser();
        }

        $cart->replaceCustomer(
            CartCustomer::fromUser($user, [
                'is_onetime_customer' => $user->isOneTimeCustomer(),
            ])
        );

        if ($customerShippingAddress = $user->getLastDeliveryAddress()) {
            $adjustedCartShipping = $cart->shipping()->adjustCustomerAddress(
                array_only($customerShippingAddress->toArray(), [
                    'salutation',
                    'company',
                    'firstname',
                    'lastname',
                    'street',
                    'number',
                    'bus',
                    'city',
                    'postal',
                    'countryid',
                ])
            );
            $cart->replaceShipping($adjustedCartShipping);
        }

        if ($customerPaymentAddress = $user->billingAddress) {
            $adjustedCartPayment = $cart->payment()->adjustCustomerAddress(
                array_only($customerPaymentAddress->toArray(), [
                    'salutation',
                    'company',
                    'firstname',
                    'lastname',
                    'street',
                    'number',
                    'bus',
                    'city',
                    'postal',
                    'countryid',
                    'vatid',
                    'valid_vat',
                ])
            );
            $cart->replacePayment($adjustedCartPayment);
        }
    }

    private function removeCustomerData(Cart $cart): void
    {
        if ($cart->customer()->exists()) {
            $cart->replaceShipping(CartShipping::empty());
            $cart->replacePayment(CartPayment::empty());
        }

        $cart->replaceCustomer(CartCustomer::empty());
    }

}
