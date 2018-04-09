<?php

namespace Thinktomorrow\Trader\Shipment\Domain\Conditions;

use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Common\Conditions\Condition;

interface ShipmentCondition extends Condition
{
    public function check(Order $order): bool;
}
