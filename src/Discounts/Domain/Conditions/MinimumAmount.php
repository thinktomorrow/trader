<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Conditions\BaseCondition;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Discounts\Domain\EligibleForDiscount;
use Thinktomorrow\Trader\Orders\Domain\Order;

class MinimumAmount extends BaseCondition implements Condition
{
    public function check(Order $order, EligibleForDiscount $eligibleForDiscount): bool
    {
        if (!isset($this->parameters['minimum_amount'])) {
            return true;
        }

        // Check subtotal (without shipment / payment costs)
        return $eligibleForDiscount->subtotal()->greaterThanOrEqual($this->parameters['minimum_amount']);
    }

    public function getParameterValues(): array
    {
        return [
            'minimum_amount' => $this->parameters['minimum_amount']->getAmount()
        ];
    }

    public function setParameterValues($values): Condition
    {
        $values = $this->normalizeParameters($values);

        $this->setParameters([
            'minimum_amount' => Cash::make($values['minimum_amount']),
        ]);

        return $this;
    }

    protected function validateParameters(array $parameters)
    {
        if (isset($parameters['minimum_amount']) && !$parameters['minimum_amount'] instanceof Money) {
            throw new \InvalidArgumentException('DiscountCondition value for minimum amount must be instance of Money.');
        }
    }
}
