<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Thinktomorrow\Trader\Common\Domain\Conditions\BaseCondition;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Common\Domain\Conditions\ItemCondition;
use Thinktomorrow\Trader\Discounts\Domain\EligibleForDiscount;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\Order;

class MinimumItemQuantity extends BaseCondition implements Condition
{
    public function check(Order $order, EligibleForDiscount $eligibleForDiscount): bool
    {
        if (!isset($this->parameters['minimum_quantity']) || $this->forOrderDiscount($eligibleForDiscount)) {
            return true;
        }

        return $this->checkItem($order, $eligibleForDiscount);
    }

    private function checkItem(Order $order, Item $item): bool
    {
        return $item->quantity() >= (int) $this->parameters['minimum_quantity'];
    }

    public function getParameterValues(): array
    {
        return [
            'minimum_quantity' => $this->parameters['minimum_quantity']->getAmount()
        ];
    }
}
