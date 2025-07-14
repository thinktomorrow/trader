<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Cash;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use Money\MoneyFormatter;
use NumberFormatter;
use Thinktomorrow\Trader\Domain\Common\Locale;

class Cash
{
    private static ?MoneyFormatter $formatter = null;
    private Money $money;

    private function __construct(Money $money)
    {
        $this->money = $money;
    }

    public static function from($money, $currencyCode = null): self
    {
        if (! $money instanceof Money) {
            $money = static::make($money, $currencyCode);
        }

        return new static($money);
    }

    /**
     * Convenience method to allow maintaining dynamic currency.
     * Keep in mind that this remains consistent across your application.
     *
     * @param string|int $amount
     * @param string|null $currencyCode
     * @return Money
     */
    public static function make(string|int $amount, ?string $currencyCode = null): Money
    {
        $currencyCode = $currencyCode ?: static::getDefaultCurrencyCode();

        return new Money($amount, new Currency($currencyCode));
    }

    public static function zero(?string $currencyCode = null): Money
    {
        $currencyCode = $currencyCode ?: static::getDefaultCurrencyCode();

        return new Money(0, new Currency($currencyCode));
    }

    /**
     * Get full string representation of amount in desired locale.
     *
     * @param int $decimals
     * @param string $dec_point
     * @param string $thousands_sep
     *
     * @return string
     */
    public function toFormat(int $decimals = 2, string $dec_point = ',', string $thousands_sep = '.'): string
    {
        // Format of decimals for currency; this could be given from the currency instead of hardcoded here
        $currency_decimals = 2;

        // First convert the integer amount to the expected decimal number according to the currency.
        $amount = $this->money->getAmount() / (10 ** $currency_decimals);
        $amount = floatval($amount);

        return number_format($amount, $decimals, $dec_point, $thousands_sep);
    }

    public function asPercentage(Money $other, $precision = 2): Percentage
    {
        if (! $this->money->isSameCurrency($other)) {
            throw new \InvalidArgumentException('Money::asPercentage expects Money value of the same currency');
        }
        if ($other->getAmount() <= 0) {
            return Percentage::fromString('0');
        }

        $percentage = number_format(($this->money->getAmount() * 100) / $other->getAmount(), $precision, '.', '');

        return Percentage::fromString($percentage);
    }

    /**
     * @return Money|int
     */
    public function percentage(Percentage|string $percentage, int $rounding_mode = Money::ROUND_HALF_UP, bool $returnAsMoney = true, ?int $round = null)
    {
        if ($percentage instanceof Percentage) {
            $percentage = $percentage->get();
        }

        $multiplier = (string)($percentage / 100);

        $money = $this->money->multiply($multiplier, $rounding_mode);

        if ($returnAsMoney) {
            return $money;
        }

        if (! $round) {
            $round = 0;
        }

        return (int)round((int)$money->getAmount(), $round, $rounding_mode);
    }

    /**
     * Add a percentage of the amount
     */
    public function addPercentage(Percentage|string $percentage, int $roundMethod = PHP_ROUND_HALF_UP): Money
    {
        if ($percentage instanceof Percentage) {
            $percentage = $percentage->get();
        }

        return $this->money->add($this->percentage($percentage, $roundMethod));
    }

    /**
     * Subtract a percentage of the amount
     *
     * @param int $percentage
     * @param int $roundMethod
     * @return Money
     */
    public function subtractPercentage($percentage, $roundMethod = PHP_ROUND_HALF_UP): Money
    {
        if ($percentage instanceof Percentage) {
            $percentage = $percentage->get();
        }

        return $this->money->subtract($this->percentage($percentage, $roundMethod));
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
    public function subtractTaxPercentage(Percentage $percentage, $roundMethod = Money::ROUND_HALF_UP, $returnAsMoney = true, $round = null): Money
    {
        $tax_percentage = (string)($percentage->toDecimal() + 1);

        return $this->money->divide($tax_percentage, $roundMethod, $returnAsMoney, $round);
    }

    //        // TODO Rate exchanger...
    //    public function convert(Currency $currency)
    //    {
    //        return RateExchange::fromMoney($this->money)->to($currency);
    //    }

    /**
     * Format according to locale preferences (default is fetched from Config)
     *
     * @param Locale $locale
     * @return string
     */
    public function toLocalizedFormat(Locale $locale): string
    {
        return $this->getSymbol() . ' ' . $this->getFormatter($locale)->format($this->money);
    }

    private static function getDefaultCurrencyCode(): string
    {
        return 'EUR';
    }

    private function getFormatter(Locale $locale): MoneyFormatter
    {
        if (! static::$formatter) {
            $currencies = new ISOCurrencies();

            $numberFormatter = new NumberFormatter($locale->toIso15897(), NumberFormatter::DECIMAL);
            $numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);

            static::$formatter = new IntlMoneyFormatter($numberFormatter, $currencies);
        }

        return static::$formatter;
    }

    private function getSymbol(): string
    {
        $code = $this->money->getCurrency()->getCode();

        switch ($code) {
            case 'EUR':
                return '€';
            case 'GBP':
                return '£';
            case 'USD':
                return '$';
        }

        return $code;
    }
}
