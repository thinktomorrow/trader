<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Shipping;

use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceValue;

final class ShippingCost implements Price
{
    use PriceValue;
}
