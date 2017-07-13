<?php

namespace Thinktomorrow\Trader\Common\Domain\Price;

use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;

class MoneyRender
{
    public function locale(Money $money, $locale = 'nl_BE')
    {
        $currencies = new ISOCurrencies();
        $moneyFormatter = new DecimalMoneyFormatter($currencies);

        // TODO format according to locale preferences
        // TODO add currency symbol

        return $moneyFormatter->format($money);
    }
}