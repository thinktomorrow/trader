<?php

namespace Thinktomorrow\Trader\Common\Domain\Price;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;
use Thinktomorrow\Trader\Common\Config;

class Cash
{
    private static $currencyCode;

    /**
     * Convenience method to allow maintaining dynamic currency.
     * Keep in mind that this remains consistent across your application.
     *
     * @param int $amount
     *
     * @return Money
     */
    public static function make($amount, $currencyCode = null): Money
    {
        $currencyCode = $currencyCode ?: static::getCurrencyCode();

        return new Money($amount, new Currency($currencyCode));
    }

    private static function getCurrencyCode(): string
    {
        if (!static::$currencyCode) {
            static::$currencyCode = (new Config())->get('currency', 'EUR');
        }

        return static::$currencyCode;
    }

    /**
     * Convenience method to reset the current currency so it can be refreshed from config.
     */
    public static function resetCurrency()
    {
        static::$currencyCode = null;
    }

    /**
     * TODO this should be something like Cash(Money)->locale() so then we can have Cash::from($money)->locale('nl').
     *
     * @param Money  $money
     * @param string $locale
     *
     * @return string
     */
    public function locale(Money $money, $locale = 'nl_BE')
    {
        $currencies = new ISOCurrencies();
        $moneyFormatter = new DecimalMoneyFormatter($currencies);

        // TODO format according to locale preferences
        // TODO add currency symbol

        // TEMPORARY display just for testing
        $symbol = $money->getCurrency()->getCode() == 'EUR' ? '&euro;' : $money->getCurrency()->getCode();

        return $symbol.$moneyFormatter->format($money);
    }
}
