<?php


namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Thinktomorrow\Trader\Order\Domain\Order;

interface Condition
{
    public function check(array $conditions, Order $order): bool;
}