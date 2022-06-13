<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Common;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Cash\Percentage;
use Thinktomorrow\Trader\Domain\Common\Locale;

trait RendersMoney
{
    protected function renderMoney(Money $money, Locale $locale): string
    {
        // Remove any 00 decimals
        return str_replace(',00', '', Cash::from($money)->toLocalizedFormat($locale));
    }

    protected function renderPercentage(Percentage $percentage): string
    {
        return $percentage->get();
    }
}
