<?php

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\PaymentMethod;

use Thinktomorrow\Trader\Application\Cart\PaymentMethod\VerifyPaymentMethodForCart;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodState;

class DefaultVerifyPaymentMethodForCart implements VerifyPaymentMethodForCart
{
    public function verify(Order $order, PaymentMethod $paymentMethod): bool
    {
        if (! in_array($paymentMethod->getState(), PaymentMethodState::onlineStates())) {
            return false;
        }

        $billingCountryId = $order->getBillingAddress()?->getAddress()->countryId;

        if ($billingCountryId && $paymentMethod->hasAnyCountries() && ! $paymentMethod->hasCountry($billingCountryId)) {
            return false;
        }

        return true;
    }
}
