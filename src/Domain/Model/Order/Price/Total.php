<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Price;

use Thinktomorrow\Trader\Domain\Common\Cash\PriceTotal;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceTotalValue;

final class Total implements PriceTotal
{
    use PriceTotalValue;
}

