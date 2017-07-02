<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Conditions\BaseCondition;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Common\Domain\Conditions\OrderCondition;
use Thinktomorrow\Trader\Order\Domain\Order;

class MinimumAmount extends BaseCondition implements Condition, OrderCondition
{
    public function check(Order $order): bool
    {
        if( ! isset($this->parameters['minimum_amount'])) return true;

        // Check subtotal (without shipment costs)
        return $order->subtotal()->greaterThanOrEqual($this->parameters['minimum_amount']);
    }

    protected function validateParameters(array $parameters)
    {
        if(isset($parameters['minimum_amount']) && ! $parameters['minimum_amount'] instanceof Money)
        {
            throw new \InvalidArgumentException('DiscountCondition value for minimum amount must be instance of Money.');
        }
    }
}