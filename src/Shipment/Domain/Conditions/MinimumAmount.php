<?php

namespace Thinktomorrow\Trader\Shipment\Domain\Conditions;

use Thinktomorrow\Trader\Common\Conditions\BaseCondition;
use Thinktomorrow\Trader\Common\Contracts\HasParameters;
use Thinktomorrow\Trader\Common\Price\Cash;
use Thinktomorrow\Trader\Orders\Domain\Order;

class MinimumAmount extends BaseCondition implements ShipmentCondition
{
    public function check(Order $order): bool
    {
        if (!isset($this->parameters['minimum_amount'])) {
            return true;
        }

        // Check subtotal (without discount or payment costs)
        return $order->subtotal()->greaterThanOrEqual($this->parameters['minimum_amount']);
    }

    public function getRawParameters(): array
    {
        return [
            'minimum_amount' => $this->parameters['minimum_amount']->getAmount()
        ];
    }

    public function setRawParameters($values): HasParameters
    {
        $values = $this->normalizeParameters($values);

        $this->setParameters([
            'minimum_amount' => Cash::make($values['minimum_amount']),
        ]);

        return $this;
    }
}
