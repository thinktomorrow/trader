<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Cash;

use Money\Currency;
use Money\Money;

/**
 * Money but with a more precise value. This is used in calculations of tax, divisions and so on to
 * provide the correct outcome and to avoid rounding errors before the calculation is done.
 */
class PreciseMoney
{
    const DEFAULT_PRECISION = 4;

    private Money $money;
    private int $precisionDecimals;

    public function __construct(Money $money, int $precisionDecimals = self::DEFAULT_PRECISION)
    {
        $this->money = $money;
        $this->precisionDecimals = $precisionDecimals;
    }

    public static function zero(int $precisionDecimals = self::DEFAULT_PRECISION, ?string $currencyCode = null): static
    {
        return new static(Cash::zero($currencyCode), $precisionDecimals);
    }

    /**
     * The float will be calculated with precision.
     *
     * @param float $float
     * @param int $precisionDecimals
     * @param string|null $currencyCode
     * @return static
     */
    public static function calculateFromFloat(float $float, int $precisionDecimals = self::DEFAULT_PRECISION, ?string $currencyCode = null): static
    {
        return new static(
            new Money((int) ($float * (pow(10, $precisionDecimals))), new Currency($currencyCode ?: 'EUR')),
            $precisionDecimals
        );
    }

    /**
     * From a float already containing the precision
     *
     * @param float $float
     * @param int $precisionDecimals
     * @param string|null $currencyCode
     * @return static
     */
    public static function fromFloat(float $float, int $precisionDecimals = self::DEFAULT_PRECISION, ?string $currencyCode = null): static
    {
        return new static(
            new Money((int) ($float), new Currency($currencyCode ?: 'EUR')),
            $precisionDecimals
        );
    }

    /**
     * Calculate the precision object with the passed decimals
     *
     * @param Money $money
     * @param int $precisionDecimals
     * @return static
     */
    public static function calculateFromMoney(Money $money, int $precisionDecimals = self::DEFAULT_PRECISION): static
    {
        return new static(
            $money->multiply(pow(10, $precisionDecimals)),
            $precisionDecimals
        );
    }

    /**
     * From already precised money
     *
     * @param Money $money
     * @param int $precisionDecimals
     * @return static
     */
    public static function fromMoney(Money $money, int $precisionDecimals = self::DEFAULT_PRECISION): static
    {
        return new static(
            $money,
            $precisionDecimals
        );
    }

    public function getPreciseMoney(): Money
    {
        return $this->money;
    }

    public function getMoney(): Money
    {
        $normalisedAmount = $this->money->divide((string) pow(10, $this->precisionDecimals))->getAmount();

        return new Money($normalisedAmount, $this->money->getCurrency());
    }

    private function changePrecision(int $newPrecision): static
    {
        $referenceFloat = $this->getPreciseMoney()->getAmount() / pow(10, $this->precisionDecimals);

        return static::calculateFromFloat(
            $referenceFloat,
            $newPrecision,
            $this->getMoney()->getCurrency()->getCode()
        );
    }

    public function negative(): static
    {
        return new static($this->money->negative(), $this->precisionDecimals);
    }

    public function add(self $other): static
    {
        return new static($this->getPreciseMoney()->add(
            $this->getOtherMoney($other)->getPreciseMoney()
        ),
            $this->precisionDecimals
        );
    }

    public function subtract(self $other): static
    {
        return new static($this->getPreciseMoney()->subtract(
            $this->getOtherMoney($other)->getPreciseMoney()
        ),
            $this->precisionDecimals
        );
    }

    /**
     * @param PreciseMoney $other
     * @return PreciseMoney
     */
    protected function getOtherMoney(PreciseMoney $other): PreciseMoney
    {
        if ($other->precisionDecimals !== $this->precisionDecimals) {
            throw new \Exception('Cannot add or subtract PreciseMoney objects. Precision decimals don\'t match. This: ['. $this->precisionDecimals.'], other: ['.$other->precisionDecimals.']');
        }

        return $other;
    }
}
