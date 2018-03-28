<?php

namespace Thinktomorrow\Trader\Payment\Domain\Conditions;

use Thinktomorrow\Trader\Common\Domain\Conditions\BaseCondition;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Orders\Domain\Order;

class MinimumAmount extends BaseCondition implements Condition
{
    public function check(Order $order): bool
    {
        if (!isset($this->parameters['minimum_amount'])) {
            return true;
        }

        // Check subtotal (without discount or payment costs)
        return $order->subtotal()->greaterThanOrEqual($this->parameters['minimum_amount']);
    }

    public function getParameterValues(): array
    {
        return [
            'minimum_amount' => $this->parameters['minimum_amount']->getAmount()
        ];
    }
}
