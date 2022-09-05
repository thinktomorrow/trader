<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Thinktomorrow\Trader\Domain\Common\Price\PriceTotal;
use Thinktomorrow\Trader\Domain\Common\Price\PriceTotalValue;

final class OrderTotal implements PriceTotal
{
    use PriceTotalValue;
}
