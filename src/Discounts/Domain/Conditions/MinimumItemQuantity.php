<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Thinktomorrow\Trader\Common\Domain\Conditions\BaseCondition;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Common\Domain\Conditions\ItemCondition;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\Order;

class MinimumItemQuantity extends BaseCondition implements Condition, ItemCondition
{
    public function check(Order $order, Item $item): bool
    {
        if (!isset($this->parameters['minimum_quantity'])) {
            return true;
        }

        return $item->quantity() >= (int) $this->parameters['minimum_quantity'];
    }
}
