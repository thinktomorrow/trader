<?php

namespace Thinktomorrow\Trader\Application\Cart\PaymentMethod;

use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;

interface VerifyPaymentMethodForCart
{
    public function verify(Order $order, PaymentMethod $paymentMethod): bool;
}
