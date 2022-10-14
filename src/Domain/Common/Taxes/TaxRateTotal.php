<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Taxes;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Cash\PreciseMoney;

class TaxRateTotal
{
    // Amount of decimals we will use to calculate the tax. This will ensure a better accuracy.
    CONST TAX_CALCULATION_PRECISION = 4;

    private TaxRate $taxRate;

    /***
     * Nett amount where tax should be calculated upon.
     * The taxableTotal has a more precise amount than the regular Money
     */
    private TaxableTotal $taxableTotal;

    public function __construct(TaxRate $taxRate, TaxableTotal $taxableTotal)
    {
        $this->taxRate = $taxRate;
        $this->taxableTotal = $taxableTotal;
    }

    public static function zero(TaxRate $taxRate): static
    {
        return new static($taxRate, TaxableTotal::zero(static::TAX_CALCULATION_PRECISION));
    }

    public function add(TaxableTotal $taxableTotal): static
    {
        return new static($this->taxRate, $this->taxableTotal->add($taxableTotal));
    }

    public function subtract(TaxableTotal $taxableTotal): static
    {
        if ($taxableTotal->getPreciseMoney()->greaterThanOrEqual($this->taxableTotal->getPreciseMoney())) {
            return new static($this->taxRate, TaxableTotal::zero(static::TAX_CALCULATION_PRECISION, $this->taxableTotal->getMoney()->getCurrency()->getCode()));
        }

        return new static($this->taxRate, $this->taxableTotal->subtract($taxableTotal));
    }

    public function getTaxRate(): TaxRate
    {
        return $this->taxRate;
    }

    public function getTaxableTotal(): TaxableTotal
    {
        return $this->taxableTotal;
    }

    public function getTaxTotal(): Money
    {
        $grossTotal = Cash::from($this->taxableTotal->getMoney())->addPercentage($this->taxRate->toPercentage());

        return $grossTotal->subtract($this->taxableTotal->getMoney());
    }

    public function getPreciseTaxTotal(): PreciseMoney
    {
        $grossTotalAmount = $this->taxableTotal->getPreciseMoney()->getAmount() * ((float) $this->taxRate->toPercentage()->toDecimal() + 1);
        $grossTotal = TaxableTotal::fromFloat($grossTotalAmount, static::TAX_CALCULATION_PRECISION);

        return $grossTotal->subtract($this->taxableTotal);
    }
}
