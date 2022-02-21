<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Line;

use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceValue;

class LinePrice implements Price
{
    use PriceValue;
}
