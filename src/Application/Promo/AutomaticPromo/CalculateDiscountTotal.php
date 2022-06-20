<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\AutomaticPromo;

use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

class CalculateDiscountTotal
{
    public function __construct()
    {
    }

    public function calculate(Order $order, Discount $discount): DiscountTotal
    {
        // In another class we should determine - based on conditions / rules - which discounts are applicable.

        // Here we calc based on the
        // type of calculation (determined by type of discount)
        // - discount base total (determined by type of discount)

        // DiscountType has dedicated class: applicable(), apply()...
    }
}
