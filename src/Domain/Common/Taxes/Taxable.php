<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Taxes;

interface Taxable
{
    public function getTaxRate(): TaxRate;

    public function getTaxableTotal(): TaxableTotal;
}
