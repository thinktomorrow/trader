<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Taxes;

use Money\Money;

interface Taxable
{
    public function getTaxRate(): TaxRate;

    public function getTaxableTotal(): Money;
}