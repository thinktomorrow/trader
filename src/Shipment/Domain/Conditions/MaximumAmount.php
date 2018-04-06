<?php

namespace Thinktomorrow\Trader\Shipment\Domain\Conditions;

use Thinktomorrow\Trader\Common\Domain\Conditions\BaseCondition;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Orders\Domain\Order;

class MaximumAmount extends BaseCondition implements Condition
{
    public function check(Order $order): bool
    {
        if (!isset($this->parameters['maximum_amount'])) {
            return true;
        }

        // Check subtotal (without discount or payment costs)
        return $order->subtotal()->lessThanOrEqual($this->parameters['maximum_amount']);
    }

    public function getParameterValues(): array
    {
        return [
            'maximum_amount' => $this->parameters['maximum_amount']->getAmount()
        ];
    }

    public function setParameterValues($values): Condition
    {
        $values = $this->normalizeParameters($values);

        $this->setParameters([
            'maximum_amount' => Cash::make($values['maximum_amount']),
        ]);

        return $this;
    }
}
