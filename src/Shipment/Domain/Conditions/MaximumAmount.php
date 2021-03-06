<?php

namespace Thinktomorrow\Trader\Shipment\Domain\Conditions;

use Thinktomorrow\Trader\Common\Conditions\BaseCondition;
use Thinktomorrow\Trader\Common\Contracts\HasParameters;
use Thinktomorrow\Trader\Common\Price\Cash;
use Thinktomorrow\Trader\Orders\Domain\Order;

class MaximumAmount extends BaseCondition implements ShipmentCondition
{
    public function check(Order $order): bool
    {
        if (!isset($this->parameters['maximum_amount'])) {
            return true;
        }

        // Check subtotal (without discount or payment costs)
        return $order->subtotal()->lessThanOrEqual($this->parameters['maximum_amount']);
    }

    public function getRawParameters(): array
    {
        return [
            'maximum_amount' => $this->parameters['maximum_amount']->getAmount(),
        ];
    }

    public function setRawParameters($values): HasParameters
    {
        $values = $this->normalizeParameters($values);

        $this->setParameters([
            'maximum_amount' => Cash::make($values['maximum_amount']),
        ]);

        return $this;
    }
}
