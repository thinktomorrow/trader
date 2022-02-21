<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Discount;

use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceValue;

final class DiscountTotal implements Price
{
    use PriceValue;
}
