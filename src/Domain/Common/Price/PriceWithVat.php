<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;

interface PriceWithVat extends Price
{
    public function getIncludingVat(): Money;

    public function getVatTotal(): Money;
}
