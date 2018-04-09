<?php

namespace Thinktomorrow\Trader\Payment\Domain\Conditions;

use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Common\Conditions\Condition;

interface PaymentCondition extends Condition
{
    public function check(Order $order): bool;
}
