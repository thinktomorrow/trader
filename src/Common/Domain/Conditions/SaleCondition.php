<?php

namespace Thinktomorrow\Trader\Common\Domain\Conditions;

use Thinktomorrow\Trader\Sales\Domain\EligibleForSale;

interface SaleCondition
{
    /**
     * Check if this condition matches the given order.
     *
     * @param Order $order
     * @return bool
     */
    public function check(EligibleForSale $eligibleForSale): bool;
}
