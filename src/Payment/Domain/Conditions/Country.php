<?php

namespace Thinktomorrow\Trader\Payment\Domain\Conditions;

use Thinktomorrow\Trader\Common\Conditions\BaseCondition;
use Thinktomorrow\Trader\Orders\Domain\Order;

class Country extends BaseCondition implements PaymentCondition
{
    public function check(Order $order): bool
    {
        if (!isset($this->parameters['country'])) {
            return true;
        }

        return $this->parameters['country'] == $order->billingAddress('country_key');
    }
}
