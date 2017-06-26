<?php


namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Domain\Order;

interface ItemCondition
{
    public function check(array $conditions, Order $order, Item $item): bool;
}