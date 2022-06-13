<?php

namespace Tests\Unit\Common\Taxes;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Taxes\Taxable;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRateTotals;

class TaxRateTotalsTest extends TestCase
{
    /** @test */
    public function it_can_sum_up_taxes()
    {
        $sum = TaxRateTotals::zero();

        $sum = $sum->addTaxableTotal(TaxRate::fromString('6'), Money::EUR(100));
        $sum = $sum->addTaxableTotal(TaxRate::fromString('6'), Money::EUR(100));
        $sum = $sum->addTaxableTotal(TaxRate::fromString('20'), Money::EUR(100));

        $this->assertCount(2, $sum->get());
        $this->assertEquals(Money::EUR(300), $sum->getTaxableTotal());
        $this->assertEquals(Money::EUR(32), $sum->getTaxTotal());
        $this->assertEquals(TaxRate::fromString('6'), $sum->find(TaxRate::fromString('6'))->getTaxRate());
        $this->assertEquals(TaxRate::fromString('20'), $sum->find(TaxRate::fromString('20'))->getTaxRate());
        $this->assertEquals(Money::EUR(200), $sum->find(TaxRate::fromString('6'))->getTaxableTotal());
        $this->assertEquals(Money::EUR(100), $sum->find(TaxRate::fromString('20'))->getTaxableTotal());
    }

    /** @test */
    public function it_can_subtract_taxes()
    {
        $sum = TaxRateTotals::zero();

        $sum = $sum->addTaxableTotal(TaxRate::fromString('6'), Money::EUR(100));
        $sum = $sum->addTaxableTotal(TaxRate::fromString('6'), Money::EUR(100));
        $sum = $sum->addTaxableTotal(TaxRate::fromString('20'), Money::EUR(100));

        $this->assertEquals(Money::EUR(100), $sum->find(TaxRate::fromString('20'))->getTaxableTotal());
        $this->assertEquals(Money::EUR(300), $sum->getTaxableTotal());

        $sum = $sum->subtractTaxableTotal(TaxRate::fromString('20'), Money::EUR(60));
        $this->assertEquals(Money::EUR(40), $sum->find(TaxRate::fromString('20'))->getTaxableTotal());
        $this->assertEquals(Money::EUR(240), $sum->getTaxableTotal());

        $sum = $sum->subtractTaxableTotal(TaxRate::fromString('6'), Money::EUR(60));
        $this->assertEquals(Money::EUR(140), $sum->find(TaxRate::fromString('6'))->getTaxableTotal());
        $this->assertEquals(Money::EUR(180), $sum->getTaxableTotal());
    }

    /** @test */
    public function it_cannot_subtract_below_zero()
    {
        $sum = TaxRateTotals::zero();

        $sum = $sum->addTaxableTotal(TaxRate::fromString('6'), Money::EUR(100));
        $sum = $sum->subtractTaxableTotal(TaxRate::fromString('6'), Money::EUR(200));
        $this->assertEquals(Money::EUR(0), $sum->find(TaxRate::fromString('6'))->getTaxableTotal());
        $this->assertEquals(Money::EUR(0), $sum->getTaxableTotal());
        $this->assertEquals(Money::EUR(0), $sum->getTaxTotal());
    }

    /** @test */
    public function it_can_sum_up_taxes_of_taxable_items()
    {
        $sum = TaxRateTotals::fromTaxables($this->dummyTaxablesEqualTaxRate());

        $this->assertCount(1, $sum->get());
        $this->assertEquals(Money::EUR(200), $sum->getTaxableTotal());
        $this->assertEquals(Money::EUR(12), $sum->getTaxTotal());
        $this->assertEquals(TaxRate::fromString('6'), $sum->find(TaxRate::fromString('6'))->getTaxRate());
        $this->assertEquals(Money::EUR(200), $sum->find(TaxRate::fromString('6'))->getTaxableTotal());
    }

    /** @test */
    public function it_can_sum_up_taxes_of_taxable_items_with_different_rates()
    {
        $sum = TaxRateTotals::fromTaxables($this->dummyTaxables());

        $this->assertCount(2, $sum->get());
        $this->assertEquals(Money::EUR(300), $sum->getTaxableTotal());
        $this->assertEquals(Money::EUR(12 + 12 + 6), $sum->getTaxTotal());
        $this->assertEquals(TaxRate::fromString('6'), $sum->find(TaxRate::fromString('6'))->getTaxRate());
        $this->assertEquals(TaxRate::fromString('12'), $sum->find(TaxRate::fromString('12'))->getTaxRate());
        $this->assertEquals(Money::EUR(100), $sum->find(TaxRate::fromString('6'))->getTaxableTotal());
        $this->assertEquals(Money::EUR(200), $sum->find(TaxRate::fromString('12'))->getTaxableTotal());
    }

    /** @test */
    public function it_can_calculate_tax_of_a_rate_total()
    {
        $sum = TaxRateTotals::fromTaxables($this->dummyTaxablesEqualTaxRate());

        $this->assertEquals(Money::EUR(12), $sum->find(TaxRate::fromString('6'))->getTaxTotal());
    }

    /** @test */
    public function it_can_calculate_taxes_of_different_rate_totals()
    {
        $sum = TaxRateTotals::fromTaxables($this->dummyTaxables());

        $this->assertEquals(Money::EUR(6), $sum->find(TaxRate::fromString('6'))->getTaxTotal());
        $this->assertEquals(Money::EUR(24), $sum->find(TaxRate::fromString('12'))->getTaxTotal());
    }

    private function dummyTaxablesEqualTaxRate(): array
    {
        return [
            new class implements Taxable {
                public function getTaxRate(): TaxRate
                {
                    return TaxRate::fromString('6');
                }

                public function getTaxableTotal(): Money
                {
                    return Money::EUR(100);
                }
            },
            new class implements Taxable {
                public function getTaxRate(): TaxRate
                {
                    return TaxRate::fromString('6');
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
                    return TaxRate::fromString('6');
                }

                public function getTaxableTotal(): Money
                {
                    return Money::EUR(100);
                }
            },
            new class implements Taxable {
                public function getTaxRate(): TaxRate
                {
                    return TaxRate::fromString('12');
                }

                public function getTaxableTotal(): Money
                {
                    return Money::EUR(100);
                }
            },
            new class implements Taxable {
                public function getTaxRate(): TaxRate
                {
                    return TaxRate::fromString('12');
                }

                public function getTaxableTotal(): Money
                {
                    return Money::EUR(100);
                }
            },
        ];
    }
}
