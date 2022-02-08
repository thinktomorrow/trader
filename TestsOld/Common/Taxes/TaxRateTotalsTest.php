<?php

namespace Thinktomorrow\Trader\Tests\Common\Taxes;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Common\Domain\Taxes\Taxable;
use Common\Domain\Taxes\TaxRate;
use Common\Domain\Taxes\TaxRateTotals;

class TaxRateTotalsTest extends TestCase
{
    /** @test */
    function it_can_sum_up_taxes_of_taxable_items()
    {
        $sum = TaxRateTotals::fromTaxables($this->dummyTaxablesEqualTaxRate());

        $this->assertCount(1, $sum->get());
        $this->assertEquals(TaxRate::fromPercent(6), $sum->get()[6]['percent']);
        $this->assertEquals(Money::EUR(100+100), $sum->get()[6]['total']);
    }

    /** @test */
    function it_can_sum_up_taxes_of_taxable_items_with_different_rates()
    {
        $sum = TaxRateTotals::fromTaxables($this->dummyTaxables());

        $this->assertCount(2, $sum->get());
        $this->assertEquals(TaxRate::fromPercent(6), $sum->get()[6]['percent']);
        $this->assertEquals(TaxRate::fromPercent(12), $sum->get()[12]['percent']);
        $this->assertEquals(Money::EUR(100), $sum->get()[6]['total']);
        $this->assertEquals(Money::EUR(100+100), $sum->get()[12]['total']);
    }

    /** @test */
    public function it_can_calculate_tax_of_a_rate_total()
    {
        $sum = TaxRateTotals::fromTaxables($this->dummyTaxablesEqualTaxRate());

        $this->assertEquals(Money::EUR(11), $sum->get()[6]['tax']);
    }

    /** @test */
    public function it_can_calculate_taxes_of_different_rate_totals()
    {
        $sum = TaxRateTotals::fromTaxables($this->dummyTaxables());

        $this->assertEquals(Money::EUR(6), $sum->get()[6]['tax']);
        $this->assertEquals(Money::EUR(21), $sum->get()[12]['tax']);
    }

    private function dummyTaxablesEqualTaxRate(): array
    {
        return [
            new class implements Taxable
            {
                public function taxRate(): TaxRate
                {
                    return TaxRate::fromPercent(6);
                }

                public function taxableTotal(): Money
                {
                    return Money::EUR(100);
                }
            },
            new class implements Taxable
            {
                public function taxRate(): TaxRate
                {
                    return TaxRate::fromPercent(6);
                }

                public function taxableTotal(): Money
                {
                    return Money::EUR(100);
                }
            },
        ];
    }

    private function dummyTaxables(): array
    {
        return [
            new class implements Taxable
            {
                public function taxRate(): TaxRate
                {
                    return TaxRate::fromPercent(6);
                }

                public function taxableTotal(): Money
                {
                    return Money::EUR(100);
                }
            },
            new class implements Taxable
            {
                public function taxRate(): TaxRate
                {
                    return TaxRate::fromPercent(12);
                }

                public function taxableTotal(): Money
                {
                    return Money::EUR(100);
                }
            },
            new class implements Taxable
            {
                public function taxRate(): TaxRate
                {
                    return TaxRate::fromPercent(12);
                }

                public function taxableTotal(): Money
                {
                    return Money::EUR(100);
                }
            },
        ];
    }
}
