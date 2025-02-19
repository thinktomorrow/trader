<?php

namespace Tests\Unit\Common\Taxes;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Vat\VatApplicable;
use Thinktomorrow\Trader\Domain\Common\Vat\VatApplicableTotal;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Common\Vat\VatTotals;

class TaxRateTotalsTest extends TestCase
{
    public function test_it_can_sum_up_taxes()
    {
        $sum = VatTotals::zero();

        $sum = $sum->addVatApplicableTotal(VatPercentage::fromString('6'), VatApplicableTotal::calculateFromMoney(Money::EUR(100)));
        $sum = $sum->addVatApplicableTotal(VatPercentage::fromString('6'), VatApplicableTotal::calculateFromMoney(Money::EUR(100)));
        $sum = $sum->addVatApplicableTotal(VatPercentage::fromString('20'), VatApplicableTotal::calculateFromMoney(Money::EUR(100)));

        $this->assertCount(2, $sum->get());
        $this->assertEquals(Money::EUR(300), $sum->getVatApplicableTotal());
        $this->assertEquals(Money::EUR(32), $sum->getVatTotal());
        $this->assertEquals(VatPercentage::fromString('6'), $sum->find(VatPercentage::fromString('6'))->getVatPercentage());
        $this->assertEquals(VatPercentage::fromString('20'), $sum->find(VatPercentage::fromString('20'))->getVatPercentage());
        $this->assertEquals(VatApplicableTotal::calculateFromMoney(Money::EUR(200)), $sum->find(VatPercentage::fromString('6'))->getVatApplicableTotal());
        $this->assertEquals(VatApplicableTotal::calculateFromMoney(Money::EUR(100)), $sum->find(VatPercentage::fromString('20'))->getVatApplicableTotal());
    }

    public function test_it_can_subtract_taxes()
    {
        $sum = VatTotals::zero();

        $sum = $sum->addVatApplicableTotal(VatPercentage::fromString('6'), VatApplicableTotal::calculateFromMoney(Money::EUR(100)));
        $sum = $sum->addVatApplicableTotal(VatPercentage::fromString('6'), VatApplicableTotal::calculateFromMoney(Money::EUR(100)));
        $sum = $sum->addVatApplicableTotal(VatPercentage::fromString('20'), VatApplicableTotal::calculateFromMoney(Money::EUR(100)));

        $this->assertEquals(VatApplicableTotal::calculateFromMoney(Money::EUR(100)), $sum->find(VatPercentage::fromString('20'))->getVatApplicableTotal());
        $this->assertEquals(Money::EUR(300), $sum->getVatApplicableTotal());

        $sum = $sum->subtractVatApplicableTotal(VatPercentage::fromString('20'), VatApplicableTotal::calculateFromMoney(Money::EUR(60)));
        $this->assertEquals(VatApplicableTotal::calculateFromMoney(Money::EUR(40)), $sum->find(VatPercentage::fromString('20'))->getVatApplicableTotal());
        $this->assertEquals(Money::EUR(240), $sum->getVatApplicableTotal());

        $sum = $sum->subtractVatApplicableTotal(VatPercentage::fromString('6'), VatApplicableTotal::calculateFromMoney(Money::EUR(60)));
        $this->assertEquals(VatApplicableTotal::calculateFromMoney(Money::EUR(140)), $sum->find(VatPercentage::fromString('6'))->getVatApplicableTotal());
        $this->assertEquals(Money::EUR(180), $sum->getVatApplicableTotal());
    }

    public function test_it_cannot_subtract_below_zero()
    {
        $sum = VatTotals::zero();

        $sum = $sum->addVatApplicableTotal(VatPercentage::fromString('6'), VatApplicableTotal::calculateFromMoney(Money::EUR(100)));
        $sum = $sum->subtractVatApplicableTotal(VatPercentage::fromString('6'), VatApplicableTotal::calculateFromMoney(Money::EUR(200)));
        $this->assertEquals(VatApplicableTotal::calculateFromMoney(Money::EUR(0)), $sum->find(VatPercentage::fromString('6'))->getVatApplicableTotal());
        $this->assertEquals(Money::EUR(0), $sum->getVatApplicableTotal());
        $this->assertEquals(Money::EUR(0), $sum->getVatTotal());
    }

    public function test_it_can_sum_up_taxes_of_taxable_items()
    {
        $sum = VatTotals::fromVatApplicables($this->dummyTaxablesEqualTaxRate());

        $this->assertCount(1, $sum->get());
        $this->assertEquals(Money::EUR(200), $sum->getVatApplicableTotal());
        $this->assertEquals(Money::EUR(12), $sum->getVatTotal());
        $this->assertEquals(VatPercentage::fromString('6'), $sum->find(VatPercentage::fromString('6'))->getVatPercentage());
        $this->assertEquals(VatApplicableTotal::calculateFromMoney(Money::EUR(200)), $sum->find(VatPercentage::fromString('6'))->getVatApplicableTotal());
    }

    public function test_it_can_sum_up_taxes_of_taxable_items_with_different_rates()
    {
        $sum = VatTotals::fromVatApplicables($this->dummyTaxables());

        $this->assertCount(2, $sum->get());
        $this->assertEquals(Money::EUR(300), $sum->getVatApplicableTotal());
        $this->assertEquals(Money::EUR(12 + 12 + 6), $sum->getVatTotal());
        $this->assertEquals(VatPercentage::fromString('6'), $sum->find(VatPercentage::fromString('6'))->getVatPercentage());
        $this->assertEquals(VatPercentage::fromString('12'), $sum->find(VatPercentage::fromString('12'))->getVatPercentage());
        $this->assertEquals(VatApplicableTotal::calculateFromMoney(Money::EUR(100)), $sum->find(VatPercentage::fromString('6'))->getVatApplicableTotal());
        $this->assertEquals(VatApplicableTotal::calculateFromMoney(Money::EUR(200)), $sum->find(VatPercentage::fromString('12'))->getVatApplicableTotal());
    }

    public function test_it_can_calculate_tax_of_a_rate_total()
    {
        $sum = VatTotals::fromVatApplicables($this->dummyTaxablesEqualTaxRate());

        $this->assertEquals(Money::EUR(12), $sum->find(VatPercentage::fromString('6'))->getVatTotal());
    }

    public function test_it_can_calculate_taxes_of_different_rate_totals()
    {
        $sum = VatTotals::fromVatApplicables($this->dummyTaxables());

        $this->assertEquals(Money::EUR(6), $sum->find(VatPercentage::fromString('6'))->getVatTotal());
        $this->assertEquals(Money::EUR(24), $sum->find(VatPercentage::fromString('12'))->getVatTotal());
    }

    private function dummyTaxablesEqualTaxRate(): array
    {
        return [
            new class implements VatApplicable {
                public function getVatPercentage(): VatPercentage
                {
                    return VatPercentage::fromString('6');
                }

                public function getVatApplicableTotal(): VatApplicableTotal
                {
                    return VatApplicableTotal::calculateFromMoney(Money::EUR(100));
                }
            },
            new class implements VatApplicable {
                public function getVatPercentage(): VatPercentage
                {
                    return VatPercentage::fromString('6');
                }

                public function getVatApplicableTotal(): VatApplicableTotal
                {
                    return VatApplicableTotal::calculateFromMoney(Money::EUR(100));
                }
            },
        ];
    }

    private function dummyTaxables(): array
    {
        return [
            new class implements VatApplicable {
                public function getVatPercentage(): VatPercentage
                {
                    return VatPercentage::fromString('6');
                }

                public function getVatApplicableTotal(): VatApplicableTotal
                {
                    return VatApplicableTotal::calculateFromMoney(Money::EUR(100));
                }
            },
            new class implements VatApplicable {
                public function getVatPercentage(): VatPercentage
                {
                    return VatPercentage::fromString('12');
                }

                public function getVatApplicableTotal(): VatApplicableTotal
                {
                    return VatApplicableTotal::calculateFromMoney(Money::EUR(100));
                }
            },
            new class implements VatApplicable {
                public function getVatPercentage(): VatPercentage
                {
                    return VatPercentage::fromString('12');
                }

                public function getVatApplicableTotal(): VatApplicableTotal
                {
                    return VatApplicableTotal::calculateFromMoney(Money::EUR(100));
                }
            },
        ];
    }
}
