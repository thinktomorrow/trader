<?php

namespace Thinktomorrow\Trader\Common\Domain\Conditions;

use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\Order;

interface ItemCondition
{
    public function check(Order $order, Item $item): bool;
}
