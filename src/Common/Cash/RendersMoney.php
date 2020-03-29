<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Common\Cash;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Locales\LocaleId;

trait RendersMoney
{
    protected function renderMoney(Money $money, LocaleId $localeId): string
    {
        // TODO: pass proper locale from above...
        return Cash::from($money)->locale($localeId);
    }

    protected function renderPercentage(Percentage $percentage): string
    {
        return $percentage->asPercent();
    }

    protected function renderMoneyAsNett(Money $money, Percentage $taxRate, LocaleId $localeId): string
    {
        $nett = Cash::from($money)->subtractTaxPercentage($taxRate);

        return $this->renderMoney($nett, $localeId);
    }
}
