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

    public function getUnitPriceExcl(): Money
    {
        return $this->unitPriceExcl;
    }

    public function getUnitPriceIncl(): Money
    {
        return $this->unitPriceIncl;
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
