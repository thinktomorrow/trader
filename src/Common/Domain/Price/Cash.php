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
    private static $defaultLocale;
    private static $formatter;

    /**
     * @var Money
     */
    private $money;

    private function __construct(Money $money)
    {
        $this->money = $money;
    }

    public static function from($money, $currencyCode = null): self
    {
        if( ! $money instanceof Money) $money = static::make($money, $currencyCode);

        return new static($money);
    }

    /**
     * Convenience method to allow maintaining dynamic currency.
     * Keep in mind that this remains consistent across your application.
     *
     * @param int  $amount
     * @param null $currencyCode
     *
     * @return Money
     */
    public static function make($amount, $currencyCode = null): Money
    {
        $currencyCode = $currencyCode ?: static::getCurrencyCode();

        return new Money($amount, new Currency($currencyCode));
    }

    /**
     * @param string $locale
     *
     * @return string
     */
    public function locale($locale = null)
    {
        // TODO format according to locale preferences (default is fetched from Config)
        // TODO add currency symbol
        $locale = $locale ?: $this->getDefaultLocale();

        // TEMPORARY display just for testing
        return $this->getSymbol().$this->getFormatter()->format($this->money);
    }

    private static function getCurrencyCode(): string
    {
        if (!static::$currencyCode) {
            static::$currencyCode = (new Config())->get('currency', 'EUR');
        }

        return static::$currencyCode;
    }

    private function getDefaultLocale(): string
    {
        if (!static::$defaultLocale) {
            static::$defaultLocale = (new Config())->get('locale', 'en-US');
        }

        return static::$defaultLocale;
    }

    private function getFormatter()
    {
        if (!static::$formatter) {
            $currencies = new ISOCurrencies();
            static::$formatter = new DecimalMoneyFormatter($currencies);
        }

        return static::$formatter;
    }

    /**
     * @return string
     */
    private function getSymbol(): string
    {
        $code = $this->money->getCurrency()->getCode();

        switch ($code) {
            case 'EUR':
                return '&euro;';
                break;
            case 'USD':
                return '&dollar;';
                break;
        }

        return $code;
    }

    /**
     * Convenience method to reset the imported config settings
     * so it can be refreshed from config.
     */
    public static function reset()
    {
        static::$currencyCode = null;
        static::$defaultLocale = null;
    }
}
