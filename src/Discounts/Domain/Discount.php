<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Thinktomorrow\Trader\Order\Domain\Order;

interface Discount
{
    public function id(): DiscountId;

    public function apply(Order $order);
}
