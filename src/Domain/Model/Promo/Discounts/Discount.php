<?php

namespace Thinktomorrow\Trader\Domain\Model\Promo\Discounts;

use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

interface Discount
{
    public function isApplicable(Order $order);

    public function getDiscountTotal(): DiscountTotal;
}
