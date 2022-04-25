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

    private function __construct()
    {
        //
    }

    public static function fromScalars(string|int $amount, string $currency, string $taxRate, bool $includesVat): static
    {
        return static::fromMoney(
            Cash::make($amount, $currency),
            TaxRate::fromString($taxRate),
            $includesVat
        );
    }

    public static function zero(): static
    {
        // TODO: how to get default settings for this here?
        return static::fromScalars('0','EUR','0',true);
    }

    public static function fromPrice(Price $otherPrice): static
    {
        $price = new static();

        $price->money = $otherPrice->getMoney();
        $price->taxRate = $otherPrice->getTaxRate();
        $price->includesVat = $otherPrice->includesVat();

        return $price;
    }

    public static function fromMoney(Money $money, TaxRate $taxRate, bool $includesVat): static
    {
        $price = new static();

        $price->money = $money;
        $price->taxRate = $taxRate;
        $price->includesVat = $includesVat;

        return $price;
    }

    public function getIncludingVat(): Money
    {
        if($this->includesVat) {
            return $this->money;
        }

        return Cash::from($this->money)->addPercentage(
            $this->taxRate->toPercentage()
        );
    }

    public function getExcludingVat(): Money
    {
        if(! $this->includesVat) {
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
        $otherMoney = $this->includesVat()
            ? $otherPrice->getIncludingVat()
            : $otherPrice->getExcludingVat();

        return static::fromMoney($this->money->add($otherMoney), $this->taxRate, $this->includesVat);
    }

    public function subtract(Price $otherPrice): static
    {
        $otherMoney = $this->includesVat()
            ? $otherPrice->getIncludingVat()
            : $otherPrice->getExcludingVat();

        return static::fromMoney($this->money->subtract($otherMoney), $this->taxRate, $this->includesVat);
    }
}
