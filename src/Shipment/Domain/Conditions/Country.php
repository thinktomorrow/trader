<?php

namespace Thinktomorrow\Trader\Shipment\Domain\Conditions;

use Thinktomorrow\Trader\Common\Conditions\BaseCondition;
use Thinktomorrow\Trader\Orders\Domain\Order;

class Country extends BaseCondition implements ShipmentCondition
{
    public function check(Order $order): bool
    {
        if (!isset($this->parameters['country'])) {
            return true;
        }

        // TODO: match country to order shipping country
        return $this->parameters['country'] == $order->shippingAddress('country_key');
    }
}
