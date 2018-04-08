<?php

namespace Thinktomorrow\Trader\Tax\Domain\Rules;

use Thinktomorrow\Trader\Common\Price\Percentage;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Tax\Domain\Taxable;
use Thinktomorrow\Trader\Tax\Domain\TaxRate;

interface TaxRule
{
    public function context(TaxRate $taxRate, Taxable $taxable = null, Order $order = null);

    public function applicable(): bool;

    public function apply(Percentage $taxPercentage): Percentage;
}
