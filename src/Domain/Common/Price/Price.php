<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;

interface Price
{
    public function getExcludingVat(): Money;
}
