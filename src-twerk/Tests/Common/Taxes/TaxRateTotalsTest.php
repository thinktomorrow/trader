<?php

namespace Thinktomorrow\Trader\Tests\Common\Taxes;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Taxes\Taxable;
use Thinktomorrow\Trader\Taxes\TaxRate;
use Thinktomorrow\Trader\Taxes\TaxRateTotals;

class TaxRateTotalsTest extends TestCase
{
    /** @test */
    public function it_can_sum_up_taxes_of_taxable_items()
    {
        $sum = TaxRateTotals::fromTaxables($this->dummyTaxablesEqualTaxRate());

        $this->assertCount(1, $sum->get());
        $this->assertEquals(TaxRate::fromInteger(6)->toPercentage(), $sum->get()[6]['percent']);
        $this->assertEquals(Money::EUR(100 + 100), $sum->get()[6]['total']);
    }

    /** @test */
    public function it_can_sum_up_taxes_of_taxable_items_with_different_rates()
    {
        $sum = TaxRateTotals::fromTaxables($this->dummyTaxables());

        $this->assertCount(2, $sum->get());
        $this->assertEquals(TaxRate::fromInteger(6)->toPercentage(), $sum->get()[6]['percent']);
        $this->assertEquals(TaxRate::fromInteger(12)->toPercentage(), $sum->get()[12]['percent']);
        $this->assertEquals(Money::EUR(100), $sum->get()[6]['total']);
        $this->assertEquals(Money::EUR(100 + 100), $sum->get()[12]['total']);
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
            new class implements Taxable {
                public function getTaxRate(): TaxRate
                {
                    return TaxRate::fromInteger(6);
                }

                public function getTaxableTotal(): Money
                {
                    return Money::EUR(100);
                }
            },
            new class implements Taxable {
                public function getTaxRate(): TaxRate
                {
                    return TaxRate::fromInteger(6);
                }

                public function getTaxableTotal(): Money
                {
                    return Money::EUR(100);
                }
            },
        ];
    }

    private function dummyTaxables(): array
    {
        return [
            new class implements Taxable {
                public function getTaxRate(): TaxRate
                {
                    return TaxRate::fromInteger(6);
                }

                public function getTaxableTotal(): Money
                {
                    return Money::EUR(100);
                }
            },
            new class implements Taxable {
                public function getTaxRate(): TaxRate
                {
                    return TaxRate::fromInteger(12);
                }

                public function getTaxableTotal(): Money
                {
                    return Money::EUR(100);
                }
            },
            new class implements Taxable {
                public function getTaxRate(): TaxRate
                {
                    return TaxRate::fromInteger(12);
                }

                public function getTaxableTotal(): Money
                {
                    return Money::EUR(100);
                }
            },
        ];
    }
}
