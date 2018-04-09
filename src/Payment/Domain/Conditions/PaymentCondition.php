<?php

namespace Thinktomorrow\Trader\Payment\Domain\Conditions;

use Thinktomorrow\Trader\Common\Conditions\Condition;
use Thinktomorrow\Trader\Orders\Domain\Order;

interface PaymentCondition extends Condition
{
    public function check(Order $order): bool;
}
