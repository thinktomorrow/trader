<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Payment;

use Thinktomorrow\Trader\Domain\Common\Price\DefaultServicePrice;
use Thinktomorrow\Trader\Domain\Common\Price\ServicePrice;

final class PaymentCost extends DefaultServicePrice implements ServicePrice
{
}
