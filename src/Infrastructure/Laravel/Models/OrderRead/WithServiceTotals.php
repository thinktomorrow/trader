<?php

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

use Money\Money;

trait WithServiceTotals
{
    protected Money $costPriceExcl;

    protected Money $discountPriceExcl;

    protected Money $totalPriceExcl;

    protected ?Money $costPriceIncl;

    protected ?Money $discountPriceIncl;

    protected ?Money $totalPriceIncl;

    protected function initializeServiceTotalsFromState(array $state): void
    {
        $this->costPriceExcl = Money::EUR($state['cost_excl']);
        $this->discountPriceExcl = Money::EUR($state['discount_excl']);
        $this->totalPriceExcl = Money::EUR($state['total_excl']);
        $this->costPriceIncl = isset($state['cost_incl']) ? Money::EUR($state['cost_incl']) : null;
        $this->discountPriceIncl = isset($state['discount_incl']) ? Money::EUR($state['discount_incl']) : null;
        $this->totalPriceIncl = isset($state['total_incl']) ? Money::EUR($state['total_incl']) : null;
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

    public function getCostPriceIncl(): Money
    {
        return $this->costPriceIncl ?? throw new \LogicException('Service cost including VAT has not been allocated.');
    }

    public function getDiscountPriceIncl(): Money
    {
        return $this->discountPriceIncl ?? throw new \LogicException('Service discount including VAT has not been allocated.');
    }

    public function getTotalPriceIncl(): Money
    {
        return $this->totalPriceIncl ?? throw new \LogicException('Service total including VAT has not been allocated.');
    }
}
