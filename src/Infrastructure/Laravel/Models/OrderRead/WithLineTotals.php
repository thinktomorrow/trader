<?php

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

use Money\Money;

trait WithLineTotals
{
    protected Money $unitPriceExcl;
    protected Money $unitPriceIncl;
    protected Money $discountPriceExcl;
    protected Money $discountPriceIncl;
    protected Money $totalPriceExcl;
    protected Money $totalVat;
    protected Money $totalPriceIncl;

    protected function initializeLineTotalsFromState(array $state): void
    {
        $this->unitPriceExcl = Money::EUR($state['unit_price_excl']);
        $this->unitPriceIncl = Money::EUR($state['unit_price_incl']);
        $this->totalPriceExcl = Money::EUR($state['total_excl']);
        $this->totalPriceIncl = Money::EUR($state['total_incl']);
        $this->totalVat = Money::EUR($state['total_vat']);
        $this->discountPriceExcl = Money::EUR($state['discount_excl']);
        $this->discountPriceIncl = Money::EUR($state['discount_incl']);
    }

    public function getUnitPriceExcl(): Money
    {
        return $this->unitPriceExcl;
    }

    public function getUnitPriceIncl(): Money
    {
        return $this->unitPriceIncl;
    }

    public function getDiscountedUnitPriceExcl(): Money
    {
        return $this->totalPriceExcl->divide($this->quantity);
    }

    public function getDiscountedUnitPriceIncl(): Money
    {
        return $this->totalPriceIncl->divide($this->quantity);
    }

    public function getDiscountPriceExcl(): Money
    {
        return $this->discountPriceExcl;
    }

    public function getDiscountPriceIncl(): Money
    {
        return $this->discountPriceIncl;
    }

    public function getTotalPriceExcl(): Money
    {
        return $this->totalPriceExcl;
    }

    public function getTotalVat(): Money
    {
        return $this->totalVat;
    }

    public function getTotalPriceIncl(): Money
    {
        return $this->totalPriceIncl;
    }
}
