<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;

interface Price
{
    public function getIncludingVat(): Money;

    public function getExcludingVat(): Money;

    public function getVatTotal(): Money;
}
