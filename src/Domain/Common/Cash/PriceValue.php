<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Cash;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;

trait PriceValue
{
    private Money $money;
    private TaxRate $taxRate;
    private bool $includesTax;

    private function __construct()
    {
        //
    }

    public static function fromScalars(string|int $amount, string $currency, string $taxRate, bool $includesTax): static
    {
        return static::fromMoney(
            Cash::make($amount, $currency),
            TaxRate::fromString($taxRate),
            $includesTax
        );
    }

    public static function fromPrice(Price $otherPrice): static
    {
        $price = new static();

        $price->money = $otherPrice->getMoney();
        $price->taxRate = $otherPrice->getTaxRate();
        $price->includesTax = $otherPrice->includesTax();

        return $price;
    }

    public static function fromMoney(Money $money, TaxRate $taxRate, bool $includesTax): static
    {
        $valueObject = new static();

        $valueObject->money = $money;
        $valueObject->taxRate = $taxRate;
        $valueObject->includesTax = $includesTax;

        return $valueObject;
    }

    public function getIncludingVat(): Money
    {
        if($this->includesTax) {
            return $this->money;
        }

        return Cash::from($this->money)->addPercentage(
            $this->taxRate->toPercentage()
        );
    }

    public function getExcludingVat(): Money
    {
        if(! $this->includesTax) {
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

    public function includesTax(): bool
    {
        return $this->includesTax;
    }

    public function multiply(int $quantity): static
    {
        return static::fromMoney($this->money->multiply($quantity), $this->taxRate, $this->includesTax);
    }

    public function add(Price $otherPrice): static
    {
        $otherMoney = $this->includesTax()
            ? $otherPrice->getIncludingVat()
            : $otherPrice->getExcludingVat();

        return static::fromMoney($this->money->add($otherMoney), $this->taxRate, $this->includesTax);
    }

    public function subtract(Price $otherPrice): static
    {
        $otherMoney = $this->includesTax()
            ? $otherPrice->getIncludingVat()
            : $otherPrice->getExcludingVat();

        return static::fromMoney($this->money->subtract($otherMoney), $this->taxRate, $this->includesTax);
    }
}
