<?php

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;

interface ConvertsToMoney
{
    public function getMoney(): Money;

    public function getIncludingVat(): Money;

    public function getExcludingVat(): Money;
}
