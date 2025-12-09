<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Thinktomorrow\Trader\Domain\Common\Price\Old\PriceTotal;
use Thinktomorrow\Trader\Domain\Common\Price\Old\PriceTotalValue;

final class OrderTotal implements PriceTotal
{
    use PriceTotalValue;
}
