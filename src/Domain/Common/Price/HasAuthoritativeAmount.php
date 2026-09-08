<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;

interface HasAuthoritativeAmount
{
    public function getTaxMode(): TaxMode;

    public function getAuthoritativeAmount(): Money;
}
