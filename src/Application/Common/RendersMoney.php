<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Common;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Cash\Percentage;

trait RendersMoney
{
    protected function renderMoney(Money $money, Locale $locale): string
    {
        return Cash::from($money)->toLocalizedFormat($locale);
    }

    protected function renderPercentage(Percentage $percentage): string
    {
        return $percentage->get();
    }

    protected function renderMoneyAsNett(Money $money, Percentage $taxRate, Locale $localeId): string
    {
        $nett = Cash::from($money)->subtractTaxPercentage($taxRate);

        return $this->renderMoney($nett, $localeId);
    }
}
