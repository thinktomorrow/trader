<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Line;

use Thinktomorrow\Trader\Domain\Common\Price\Old\Price;
use Thinktomorrow\Trader\Domain\Common\Price\Old\PriceValue;

class LinePrice implements Price
{
    use PriceValue;
}
