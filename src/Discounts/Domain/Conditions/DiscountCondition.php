<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Common\Conditions\Condition;
use Thinktomorrow\Trader\Discounts\Domain\EligibleForDiscount;

interface DiscountCondition extends Condition
{
    public function check(Order $order, EligibleForDiscount $eligibleForDiscount): bool;
}
