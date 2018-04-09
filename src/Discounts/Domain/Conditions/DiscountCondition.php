<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Thinktomorrow\Trader\Common\Conditions\Condition;
use Thinktomorrow\Trader\Discounts\Domain\EligibleForDiscount;
use Thinktomorrow\Trader\Orders\Domain\Order;

interface DiscountCondition extends Condition
{
    public function check(Order $order, EligibleForDiscount $eligibleForDiscount): bool;
}
