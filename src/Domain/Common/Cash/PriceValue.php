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

    public static function fromMoneyIncludingVat(Money $money, TaxRate $taxRate): static
    {
        return static::fromMoney($money, $taxRate, true);
    }

    public static function fromMoneyExcludingVat(Money $money, TaxRate $taxRate): static
    {
        return static::fromMoney($money, $taxRate, false);
    }

    private static function fromMoney(Money $money, TaxRate $taxRate, bool $includesTax): static
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

    public function getTaxRate(): TaxRate
    {
        return $this->taxRate;
    }

    public function includesTax(): bool
    {
        return $this->includesTax;
    }
}
