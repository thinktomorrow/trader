<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo;

use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;

class Promo
{
    public function isApplicable($order)
    {
        // Run over all conditions ...
    }

    public function getDiscountTotal(): DiscountTotal
    {
        // get amount of discount so we can compare to other discounts and use the one with the highest discount.
    }
}
