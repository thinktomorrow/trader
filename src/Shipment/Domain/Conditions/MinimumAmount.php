<?php

namespace Thinktomorrow\Trader\Shipment\Domain\Conditions;

use Thinktomorrow\Trader\Common\Domain\Conditions\BaseCondition;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
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

    public function setParameterValues(array $values): Condition
    {
        if(!isset($values['minimum_amount'])){
            throw new \InvalidArgumentException('Raw condition value for minimum_amount is missing');
        }

        $this->setParameters([
            'minimum_amount' => Cash::make($values['minimum_amount']),
        ]);

        return $this;
    }
}
