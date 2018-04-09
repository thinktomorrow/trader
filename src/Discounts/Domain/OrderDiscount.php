<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Thinktomorrow\Trader\Orders\Domain\Order;

interface OrderDiscount
{
    public function applicable(Order $order): bool;
}
