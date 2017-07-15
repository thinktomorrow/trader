<?php

namespace Thinktomorrow\Trader\Common\Domain\Conditions;

use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Domain\Order;

interface ItemCondition
{
    public function check(Order $order, Item $item): bool;
}