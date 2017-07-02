<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Thinktomorrow\Trader\Order\Domain\Order;

interface OrderDiscount
{
    public function applicable(Order $order): bool;
}