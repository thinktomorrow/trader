<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Shipping;

use Thinktomorrow\Trader\Domain\Common\Price\Old\Price;
use Thinktomorrow\Trader\Domain\Common\Price\Old\PriceValue;

final class ShippingCost implements Price
{
    use PriceValue;
}
