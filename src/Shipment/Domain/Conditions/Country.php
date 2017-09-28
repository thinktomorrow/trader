<?php

namespace Thinktomorrow\Trader\Shipment\Domain\Conditions;

use Thinktomorrow\Trader\Common\Domain\Conditions\BaseCondition;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Orders\Domain\Order;

class Country extends BaseCondition implements Condition
{
    public function __construct()
    {
        // TODO ShippingAddress MODEL to fetch or is this included in order?
    }

    public function check(Order $order): bool
    {
        if (!isset($this->parameters['country'])) {
            return true;
        }

        // TODO: match country to order shipping country
        return $this->parameters['country'] == $order->shippingAddress('country_key');
    }
}
