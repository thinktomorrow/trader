<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Common\Domain\Cash;

use Money\Money;
use Optiphar\Cashier\Cash;
use Optiphar\Cashier\Percentage;

trait RendersMoney
{
    protected function renderMoney(Money $money): string
    {
        return Cash::from($money)->locale();
    }

    protected function renderPercentage(Percentage $percentage): string
    {
        return $percentage->asPercent();
    }

    protected function renderMoneyAsNett(Money $money, Percentage $taxRate): string
    {
        $nett = Cash::from($money)->subtractTaxPercentage($taxRate);

        return $this->renderMoney($nett);
    }
}
