<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Taxes;

use Money\Money;
use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;

class TaxRateTotals
{
    /** @var TaxRateTotal[] */
    private iterable $taxRateTotals;

    private function __construct(iterable $taxRateTotals)
    {
        Assertion::allIsInstanceOf((array) $taxRateTotals, TaxRateTotal::class);

        $this->taxRateTotals = $taxRateTotals;
    }

    public static function fromTaxables(array $taxables): static
    {
        Assertion::allIsInstanceOf($taxables, Taxable::class);

        $taxRateTotals = static::convertTaxablesToTotals($taxables);

        return new static($taxRateTotals);
    }

    public static function zero(): static
    {
        return new static([]);
    }

    public function addTaxableTotal(TaxRate $taxRate, Money $taxableTotal): static
    {
        $taxRateTotals = $this->taxRateTotals;

        $match = false;
        foreach($taxRateTotals as $i => $taxRateTotal) {
            if($taxRateTotal->getTaxRate()->equals($taxRate)) {
                $taxRateTotals[$i] = $taxRateTotal->add($taxableTotal);
                $match = true;
            }
        }

        if(!$match) {
            $taxRateTotals[] = new TaxRateTotal($taxRate, $taxableTotal);
        }

        return new static($taxRateTotals);
    }

    public function subtractTaxableTotal(TaxRate $taxRate, Money $taxableTotal): static
    {
        $taxRateTotals = $this->taxRateTotals;

        $match = false;
        foreach($taxRateTotals as $i => $taxRateTotal) {
            if($taxRateTotal->getTaxRate()->equals($taxRate)) {
                $taxRateTotals[$i] = $taxRateTotal->subtract($taxableTotal);
                $match = true;
            }
        }

        if(!$match) {
            $taxRateTotals[] = new TaxRateTotal($taxRate, $taxableTotal->negative());
        }

        return new static($taxRateTotals);
    }

    public function get(): iterable
    {
        return $this->taxRateTotals;
    }

    public function find(TaxRate $taxRate): ?TaxRateTotal
    {
        foreach($this->taxRateTotals as $taxRateTotal) {
            if($taxRateTotal->getTaxRate()->equals($taxRate)) {
                return $taxRateTotal;
            }
        }

        return null;
    }

    public function getTaxableTotal(): Money
    {
        return array_reduce(
            $this->taxRateTotals,
            fn($carry, $taxRateTotal) => $carry->add($taxRateTotal->getTaxableTotal()),
            Cash::zero()
        );
    }

    public function getTaxTotal(): Money
    {
        $total = array_reduce(
            $this->taxRateTotals,
            fn($carry, $taxRateTotal) => $carry->add($taxRateTotal->getTaxTotal()),
            Cash::zero()
        );

        if($total->isNegative()) {
            return new Money(0, $total->getCurrency());
        }

        return $total;
    }

    /**
     * For each rate the item totals are added up. The tax amount is then calculated for each total.
     * If we would take the tax amount of each item, the tax total is prone to rounding errors.
     *
     * @return array
     */
    private static function convertTaxablesToTotals(iterable $taxables): array
    {
        /** @var TaxRateTotal[] $totalsPerRate */
        $totalsPerRate = [];

        /** @var Taxable $taxable */
        foreach ($taxables as $taxable) {
            $key = $taxable->getTaxRate()->toPercentage()->get();

            if (! isset($totalsPerRate[$key])) {
                $totalsPerRate[$key] = TaxRateTotal::zero($taxable->getTaxRate());
            }

            $totalsPerRate[$key] = $totalsPerRate[$key]->add($taxable->getTaxableTotal());
        }

        return $totalsPerRate;
    }
}
