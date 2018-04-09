<?php

namespace Thinktomorrow\Trader\Sales\Domain\Conditions;

use Thinktomorrow\Trader\Common\Conditions\Condition;
use Thinktomorrow\Trader\Sales\Domain\EligibleForSale;

interface SaleCondition extends Condition
{
    /**
     * Check if this condition matches the given order.
     *
     * @param EligibleForSale $eligibleForSale
     *
     * @return bool
     */
    public function check(EligibleForSale $eligibleForSale): bool;
}
