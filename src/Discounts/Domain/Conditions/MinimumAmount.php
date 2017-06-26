<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Thinktomorrow\Trader\Order\Domain\Order;

class MinimumAmount implements Condition
{
    public function check(array $conditions, Order $order): bool
    {
        if( ! isset($conditions['minimum_amount'])) return true;

        // Check subtotal (without shipment costs)
        return $order->subtotal()->greaterThanOrEqual($conditions['minimum_amount']);
    }
}