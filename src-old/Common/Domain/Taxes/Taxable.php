<?php

declare(strict_types=1);

namespace Common\Domain\Taxes;

use Money\Money;

interface Taxable
{
    public function taxRate(): TaxRate;

    public function taxableTotal(): Money;
}
