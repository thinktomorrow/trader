<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Thinktomorrow\Trader\Orders\Domain\Order;

interface Discount
{
    public function id(): DiscountId;

    public function apply(Order $order);
}
