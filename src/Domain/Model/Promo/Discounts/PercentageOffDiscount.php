<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo\Discounts;

use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

class PercentageOffDiscount implements Discount
{
    public function isApplicable(Order $order)
    {
        // Run over all conditions ...
    }

    public function getDiscountTotal(): DiscountTotal
    {
        // get amount of discount so we can compare to other discounts and use the one with the highest discount.
    }
}
