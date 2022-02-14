<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Payment;

use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceValue;

final class PaymentTotal implements Price
{
    use PriceValue;
}
