<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Price;

use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceValue;

final class Total implements Price
{
    use PriceValue;
}
