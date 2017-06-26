<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Domain\Order;

class MinimumItemAmount implements ItemCondition
{
    public function check(array $conditions, Order $order, Item $item): bool
    {
        if( ! isset($conditions['minimum_amount'])) return true;

        return $item->total()->greaterThanOrEqual($conditions['minimum_amount']);
    }
}