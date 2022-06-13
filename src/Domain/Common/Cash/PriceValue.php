<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Cash;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;

trait PriceValue
{
    private Money $money;
    private TaxRate $taxRate;
    private bool $includesVat;

    private function __construct(Money $money, TaxRate $taxRate, bool $includesVat)
    {
        if ($money->isNegative()) {
            throw new PriceCannotBeNegative('Price money amount cannot be negative: ' . $money->getAmount() . ' is given.');
        }

        $this->money = $money;
        $this->taxRate = $taxRate;
        $this->includesVat = $includesVat;
    }

    public static function fromScalars(string|int $amount, string $currency, string $taxRate, bool $includesVat): static
    {
        return new static(
            Cash::make($amount, $currency),
            TaxRate::fromString($taxRate),
            $includesVat
        );
    }

    public static function zero(): static
    {
        // TODO: how to get default settings for this here?
        return new static(Cash::zero(), TaxRate::fromString('0'), true);
    }

    public static function fromPrice(Price $otherPrice): static
    {
        return new static(
            $otherPrice->getMoney(),
            $otherPrice->getTaxRate(),
            $otherPrice->includesVat()
        );
    }

    public static function fromMoney(Money $money, TaxRate $taxRate, bool $includesVat): static
    {
        return new static($money, $taxRate, $includesVat);
    }

    public function getIncludingVat(): Money
    {
        if ($this->includesVat) {
            return $this->money;
        }

        return Cash::from($this->money)->addPercentage(
            $this->taxRate->toPercentage()
        );
    }

    public function getExcludingVat(): Money
    {
        if (! $this->includesVat) {
            return $this->money;
        }

        return Cash::from($this->money)->subtractTaxPercentage(
            $this->taxRate->toPercentage()
        );
    }

    public function getMoney(): Money
    {
        return $this->money;
    }

    public function getTaxRate(): TaxRate
    {
        return $this->taxRate;
    }

    public function getTaxTotal(): Money
    {
        return $this->getIncludingVat()
            ->subtract($this->getExcludingVat());
    }

    public function includesVat(): bool
    {
        return $this->includesVat;
    }

    public function multiply(int $quantity): static
    {
        return static::fromMoney($this->money->multiply($quantity), $this->taxRate, $this->includesVat);
    }

    public function add(Price $otherPrice): static
    {
        $this->assertSameTaxRates($otherPrice);

        $otherMoney = $this->includesVat()
            ? $otherPrice->getIncludingVat()
            : $otherPrice->getExcludingVat();

        return static::fromMoney($this->money->add($otherMoney), $this->taxRate, $this->includesVat);
    }

    public function subtract(Price $otherPrice): static
    {
        $this->assertSameTaxRates($otherPrice);

        $otherMoney = $this->includesVat()
            ? $otherPrice->getIncludingVat()
            : $otherPrice->getExcludingVat();

        return static::fromMoney($this->money->subtract($otherMoney), $this->taxRate, $this->includesVat);
    }

    public function changeTaxRate(TaxRate $taxRate): static
    {
        return static::fromMoney($this->getExcludingVat(), $taxRate, false);
    }

    public function addDifferent(Price $otherPrice): static
    {
        return $this->add(
            $otherPrice->changeTaxRate($this->taxRate)
        );
    }

    public function subtractDifferent(Price $otherPrice): static
    {
        return $this->subtract(
            $otherPrice->changeTaxRate($this->taxRate)
        );
    }

    private function assertSameTaxRates(Price $otherPrice): void
    {
        if (! $otherPrice->getTaxRate()->equals($this->getTaxRate())) {
            throw new PriceCannotContainMultipleTaxRates($otherPrice->getTaxRate() . ' differs from expected ' . $this->getTaxRate());
        }
    }
}
