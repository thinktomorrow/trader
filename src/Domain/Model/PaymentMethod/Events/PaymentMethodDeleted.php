<?php

namespace Thinktomorrow\Trader\Domain\Model\PaymentMethod\Events;

use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;

class PaymentMethodDeleted
{
    public function __construct(public readonly PaymentMethodId $paymentMethodId)
    {
    }
}
