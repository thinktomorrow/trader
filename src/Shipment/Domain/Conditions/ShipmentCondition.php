<?php

namespace Thinktomorrow\Trader\Shipment\Domain\Conditions;

use Thinktomorrow\Trader\Common\Conditions\Condition;
use Thinktomorrow\Trader\Orders\Domain\Order;

interface ShipmentCondition extends Condition
{
    public function check(Order $order): bool;
}
