<?php

namespace Thinktomorrow\Trader\Common\Domain\Conditions;

use Thinktomorrow\Trader\Orders\Domain\Order;

interface OrderCondition
{
    /**
     * Check if this condition matches the given order.
     *
     * @param Order $order
     *
     * @return bool
     */
    public function check(Order $order): bool;
}
