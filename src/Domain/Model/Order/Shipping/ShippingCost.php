<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Shipping;

use Thinktomorrow\Trader\Domain\Common\Price\DefaultServicePrice;
use Thinktomorrow\Trader\Domain\Common\Price\ServicePrice;

final class ShippingCost extends DefaultServicePrice implements ServicePrice
{
}
