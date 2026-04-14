<?php

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

use Money\Money;

trait WithServiceTotals
{
    protected Money $costPriceExcl;

    protected Money $discountPriceExcl;

    protected Money $totalPriceExcl;

    protected function initializeServiceTotalsFromState(array $state): void
    {
        $this->costPriceExcl = Money::EUR($state['cost_excl']);
        $this->discountPriceExcl = Money::EUR($state['discount_excl']);
        $this->totalPriceExcl = Money::EUR($state['total_excl']);
    }

    public function getCostPriceExcl(): Money
    {
        return $this->costPriceExcl;
    }

    public function getDiscountPriceExcl(): Money
    {
        return $this->discountPriceExcl;
    }

    public function getTotalPriceExcl(): Money
    {
        return $this->totalPriceExcl;
    }
}
