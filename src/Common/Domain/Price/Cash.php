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
        if (!$money instanceof Money) {
            $money = static::make($money, $currencyCode);
        }

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

    public static function zero($currencyCode = null): Money
    {
        $currencyCode = $currencyCode ?: static::getCurrencyCode();

        return new Money(0, new Currency($currencyCode));
    }

    /**
     * Get full string representation of amount in desired locale.
     *
     * @param int    $decimals
     * @param string $dec_point
     * @param string $thousands_sep
     *
     * @return string
     */
    public function toFormat($decimals = 2, $dec_point = ',', $thousands_sep = '.')
    {
        // Format of decimals for currency; this could be given from the currency instead of hardcoded here
        $currency_decimals = 2;

        // First convert the integer amount to the expected decimal number according to the currency.
        $amount = $this->money->getAmount() / (10 ** $currency_decimals);
        $amount = floatval($amount);

        return number_format($amount, $decimals, $dec_point, $thousands_sep);
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

    public function asPercentage(Money $other, $precision = 2): Percentage
    {
        if (!$this->money->isSameCurrency($other)) {
            throw new \InvalidArgumentException('Money::asPercentage expects Money value of the same currency');
        }
        if ($other->getAmount() <= 0) {
            return Percentage::fromPercent(0);
        }

        $percentage = (float) sprintf("%.".$precision."f", ($this->money->getAmount() * 100) / $other->getAmount());

        return Percentage::fromPercent($percentage);
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
