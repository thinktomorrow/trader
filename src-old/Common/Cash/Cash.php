<?php

declare(strict_types=1);

namespace Common\Cash;

use Money\Money;
use Money\Currency;
use NumberFormatter;
use Money\Currencies\ISOCurrencies;
use Common\Domain\Locales\LocaleId;
use Money\Formatter\IntlMoneyFormatter;
use Thinktomorrow\Trader\Common\Cash\RateExchange;
use function Thinktomorrow\Trader\Common\Cash\app;

class Cash
{
    private static $currencyCode;
    private static $defaultLocale;
    private static $formatter;

    /** @var Money */
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
     *
     * @param LocaleId|null $localeId
     * @return string
     */
    public function locale(LocaleId $localeId = null)
    {
        // TODO format according to locale preferences (default is fetched from Config)
        // TODO add currency symbol
        $locale = $localeId ?: $this->getDefaultLocale();

        return $this->getSymbol().' '.$this->getFormatter($locale)->format($this->money);
    }

    public function asPercentage(Money $other, $precision = 2): Percentage
    {
        if (!$this->money->isSameCurrency($other)) {
            throw new \InvalidArgumentException('Money::asPercentage expects Money value of the same currency');
        }
        if ($other->getAmount() <= 0) {
            return Percentage::fromPercent(0);
        }

        $percentage = (float) sprintf('%.'.$precision.'f', ($this->money->getAmount() * 100) / $other->getAmount());
        return Percentage::fromPercent($percentage);
    }

    /**
     * @param float $percentage
     * @param int $rounding_mode
     * @param bool $returnAsMoney
     * @param null $round
     * @return Money|int
     */
    public function percentage($percentage, $rounding_mode = Money::ROUND_HALF_UP, $returnAsMoney = true, $round = null)
    {
        if($percentage instanceof Percentage){
            $percentage = $percentage->asPercent();
        }

        $multiplier = ($percentage / 100);
        $money = $this->money->multiply($multiplier,$rounding_mode);

        if($returnAsMoney) return $money;

        if(!$round) $round = 0;
        return (int) round($money->getAmount(), $round , $rounding_mode);
    }

    /**
     * Add a percentage of the amount
     *
     * @param    integer $percentage
     * @param int $roundMethod
     * @return Money
     */
    public function addPercentage( $percentage, $roundMethod = PHP_ROUND_HALF_UP ): Money
    {
        if($percentage instanceof Percentage){
            $percentage = $percentage->asPercent();
        }

        return $this->money->add($this->percentage($percentage,$roundMethod));
    }

    /**
     * Subtract a percentage of the amount
     *
     * @param    integer $percentage
     * @param int $roundMethod
     * @return Money
     */
    public function subtractPercentage( $percentage, $roundMethod = PHP_ROUND_HALF_UP ): Money
    {
        if($percentage instanceof Percentage){
            $percentage = $percentage->asPercent();
        }

        return $this->money->subtract($this->percentage($percentage,$roundMethod));
    }

    /**
     * Subtract a tax percentage from a gross amount
     * e.g. when gross is 100 and taxrate is 20%, we will have 100 / 1.2 = 80
     *
     * @param $percentage
     * @param int $roundMethod
     * @param bool $returnAsMoney
     * @param null $round
     * @return Money
     */
    public function subtractTaxPercentage($percentage, $roundMethod = Money::ROUND_HALF_UP, $returnAsMoney = true, $round = null ): Money
    {
        if($percentage instanceof Percentage){
            $percentage = $percentage->asPercent();
        }

        $tax_percentage = ($percentage + 100) / 100;

        return $this->money->divide($tax_percentage, $roundMethod, $returnAsMoney, $round);
    }

    public function convert(Currency $currency)
    {
        // TODO Rate exchanger...
        return RateExchange::fromMoney($this->money)->to($currency);
    }

    private static function getCurrencyCode(): string
    {
        // TODO: get this from config...
        if (!static::$currencyCode) {
            static::$currencyCode = 'EUR';
        }

        return static::$currencyCode;
    }

    private function getDefaultLocale(): string
    {
        // TODO: set default locale and currency once in service provider but not here ...
        if (!static::$defaultLocale) {
            static::$defaultLocale = LocaleId::fromString(app()->getLocale());
        }

        return static::$defaultLocale;
    }

    private function getFormatter(LocaleId $localeId)
    {
        if (!static::$formatter) {

            // Based on application locale, we could set different representations of the money.
            // For now we do not differentiate and use the default nl_BE localization.
            // TODO: set this in serviceProvider
            $isoCode = 'nl_BE';

            $currencies = new ISOCurrencies();

            $numberFormatter = new NumberFormatter($isoCode, NumberFormatter::DECIMAL);
            $numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);

            static::$formatter = new IntlMoneyFormatter($numberFormatter, $currencies);
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
                return '€';
                break;
            case 'GBP':
                return '£';
                break;
            case 'USD':
                return '$';
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
