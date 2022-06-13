<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Taxes;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;

class TaxRateTotal
{
    private TaxRate $taxRate;

    /** @var Money Nett amount where tax should be calculated upon */
    private Money $taxableTotal;

    public function __construct(TaxRate $taxRate, Money $taxableTotal)
    {
        $this->taxRate = $taxRate;
        $this->taxableTotal = $taxableTotal;
    }

    public static function zero(TaxRate $taxRate): static
    {
        return new static($taxRate, Cash::zero());
    }

    public function add(Money $taxableTotal): static
    {
        return new static($this->taxRate, $this->taxableTotal->add($taxableTotal));
    }

    public function subtract(Money $taxableTotal): static
    {
        if($taxableTotal->greaterThanOrEqual($this->taxableTotal)) {
            return new static($this->taxRate, new Money(0, $this->taxableTotal->getCurrency()));
        }

        return new static($this->taxRate, $this->taxableTotal->subtract($taxableTotal));
    }

    public function getTaxRate(): TaxRate
    {
        return $this->taxRate;
    }

    public function getTaxableTotal(): Money
    {
        return $this->taxableTotal;
    }

    public function getTaxTotal(): Money
    {
        $grossTotal = Cash::from($this->taxableTotal)->addPercentage($this->taxRate->toPercentage());

        return $grossTotal->subtract($this->taxableTotal);
    }
}
