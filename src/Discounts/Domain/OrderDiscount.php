<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Thinktomorrow\Trader\Orders\Domain\Order;

interface OrderDiscountOLD
{
    public function applicable(Order $order): bool;
}
