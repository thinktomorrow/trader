<?php

namespace Tests\Unit\Common\Vat;

use Money\Exception\CurrencyMismatchException;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\Old\VatTotal;
use Thinktomorrow\Trader\Domain\Common\Price\Old\VatTotals;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

class VatTotalsTest extends TestCase
{
    public function test_it_can_sum_up_taxes()
    {
        $sum = VatTotals::zero();

        $sum = $sum->add(VatTotal::make(VatPercentage::fromString('6'), Money::EUR(100)));
        $sum = $sum->add(VatTotal::make(VatPercentage::fromString('6'), Money::EUR(100)));
        $sum = $sum->add(VatTotal::make(VatPercentage::fromString('20'), Money::EUR(100)));

        $this->assertCount(2, $sum->get());
        $this->assertEquals(Money::EUR(300), $sum->getVatTotal());
        $this->assertEquals(VatPercentage::fromString('6'), $sum->find(VatPercentage::fromString('6'))->getVatPercentage());
        $this->assertEquals(VatPercentage::fromString('20'), $sum->find(VatPercentage::fromString('20'))->getVatPercentage());
        $this->assertEquals(Money::EUR(200), $sum->find(VatPercentage::fromString('6'))->getTotal());
        $this->assertEquals(Money::EUR(100), $sum->find(VatPercentage::fromString('20'))->getTotal());
    }

    public function test_it_can_subtract_taxes()
    {
        $sum = VatTotals::zero();

        $sum = $sum->add(VatTotal::make(VatPercentage::fromString('6'), Money::EUR(100)));
        $sum = $sum->add(VatTotal::make(VatPercentage::fromString('6'), Money::EUR(100)));
        $sum = $sum->add(VatTotal::make(VatPercentage::fromString('20'), Money::EUR(100)));

        $this->assertEquals(Money::EUR(100), $sum->find(VatPercentage::fromString('20'))->getTotal());

        $sum = $sum->subtract(VatTotal::make(VatPercentage::fromString('20'), Money::EUR(60)));
        $this->assertEquals(Money::EUR(40), $sum->find(VatPercentage::fromString('20'))->getTotal());

        $sum = $sum->subtract(VatTotal::make(VatPercentage::fromString('6'), Money::EUR(60)));
        $this->assertEquals(Money::EUR(140), $sum->find(VatPercentage::fromString('6'))->getTotal());
    }

    public function test_it_cannot_subtract_below_zero()
    {
        $sum = VatTotals::zero();

        $sum = $sum->add(VatTotal::make(VatPercentage::fromString('6'), Money::EUR(100)));
        $sum = $sum->subtract(VatTotal::make(VatPercentage::fromString('6'), Money::EUR(200)));
        $this->assertEquals(Money::EUR(0), $sum->find(VatPercentage::fromString('6'))->getTotal());
        $this->assertEquals(Money::EUR(0), $sum->getVatTotal());
    }

    public function test_it_cannot_mix_currencies()
    {
        $this->expectException(CurrencyMismatchException::class);

        $sum = VatTotals::zero();
        $sum = $sum->add(VatTotal::make(VatPercentage::fromString('6'), Money::EUR(100)));
        $sum = $sum->add(VatTotal::make(VatPercentage::fromString('6'), Money::USD(100)));
    }

    public function test_find_returns_null_if_not_found()
    {
        $sum = VatTotals::zero();

        $this->assertNull($sum->find(VatPercentage::fromString('21')));
    }

    public function test_add_creates_new_vat_group()
    {
        $sum = VatTotals::zero();

        $sum = $sum->add(VatTotal::make(VatPercentage::fromString('6'), Money::EUR(100)));

        $this->assertCount(1, $sum->get());
        $this->assertEquals(Money::EUR(100), $sum->find(VatPercentage::fromString('6'))->getTotal());
    }

    public function test_subtracting_unknown_vat_percentage_should_not_create_positive_total()
    {
        $sum = VatTotals::zero();
        $sum = $sum->subtract(VatTotal::make(VatPercentage::fromString('6'), Money::EUR(50)));

        $this->assertEquals(Money::EUR(0), $sum->getVatTotal());
    }

    public function test_total_vat_never_negative()
    {
        $sum = VatTotals::zero();
        $sum = $sum->add(VatTotal::make(VatPercentage::fromString('6'), Money::EUR(50)));
        $sum = $sum->subtract(VatTotal::make(VatPercentage::fromString('6'), Money::EUR(100)));

        $this->assertEquals(Money::EUR(0), $sum->getVatTotal());
    }
}
