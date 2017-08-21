<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Conditions\BaseCondition;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Common\Domain\Conditions\ItemCondition;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Domain\Order;

class MinimumItemAmount extends BaseCondition implements Condition, ItemCondition
{
    public function check(Order $order, Item $item): bool
    {
        if (!isset($this->parameters['minimum_amount'])) {
            return true;
        }

        return $item->total()->greaterThanOrEqual($this->parameters['minimum_amount']);
    }

    protected function validateParameters(array $parameters)
    {
        if (isset($parameters['minimum_amount']) && !$parameters['minimum_amount'] instanceof Money) {
            throw new \InvalidArgumentException('DiscountCondition value for minimum amount must be instance of Money.');
        }
    }
}
