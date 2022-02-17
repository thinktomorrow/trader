<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Discount;

use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Discount\DiscountTotal;

class CalculateDiscountTotal
{
    public function __construct()
    {

    }

    public function calculate(Order $order, Discount $discount): DiscountTotal
    {
        // Here we calc based on the
        // type of calculation (determined by type of discount)
        // - discount base total (determined by type of discount)
    }
}
