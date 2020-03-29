<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Common\Domain\Taxes;

use Money\Money;

interface Taxable
{
    public function taxRate(): TaxRate;

    public function taxableTotal(): Money;
}
