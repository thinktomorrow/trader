<?php

namespace Thinktomorrow\Trader\Domain\Model\Promo\Discounts;

use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;

interface Discount
{
    public function isApplicable(Order $order);

    public function getDiscountTotal(): DiscountTotal;
}
