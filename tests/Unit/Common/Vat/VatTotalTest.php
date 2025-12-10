<?php

namespace Tests\Unit\Common\Vat;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Common\Vat\VatTotal;

class VatTotalTest extends TestCase
{
    public function test_it_can_be_created()
    {
        $vatTotal = VatTotal::make(VatPercentage::fromString('6'), Money::EUR(100));

        $this->assertEquals(VatPercentage::fromString('6'), $vatTotal->getVatPercentage());
        $this->assertEquals(Money::EUR(100), $vatTotal->getTotal());
    }

    public function test_zero_starts_at_zero_total()
    {
        $vatTotal = VatTotal::zero(VatPercentage::fromString('21'));

        $this->assertEquals(Money::EUR(0), $vatTotal->getTotal());
        $this->assertEquals(VatPercentage::fromString('21'), $vatTotal->getVatPercentage());
    }

    public function test_add_increases_total()
    {
        $vatTotal = VatTotal::make(VatPercentage::fromString('6'), Money::EUR(100));
        $updated = $vatTotal->add(Money::EUR(50));

        $this->assertEquals(Money::EUR(150), $updated->getTotal());

        // Immutable?
        $this->assertEquals(Money::EUR(100), $vatTotal->getTotal());
    }

    public function test_subtract_reduces_total()
    {
        $vatTotal = VatTotal::make(VatPercentage::fromString('6'), Money::EUR(100));
        $updated = $vatTotal->subtract(Money::EUR(30));

        $this->assertEquals(Money::EUR(70), $updated->getTotal());
        $this->assertEquals(Money::EUR(100), $vatTotal->getTotal()); // immutable
    }

    public function test_subtract_cannot_go_negative()
    {
        $vatTotal = VatTotal::make(VatPercentage::fromString('6'), Money::EUR(80));
        $updated = $vatTotal->subtract(Money::EUR(200));

        $this->assertEquals(Money::EUR(0), $updated->getTotal());
    }

    public function test_subtract_exact_to_zero()
    {
        $vatTotal = VatTotal::make(VatPercentage::fromString('6'), Money::EUR(50));
        $updated = $vatTotal->subtract(Money::EUR(50));

        $this->assertEquals(Money::EUR(0), $updated->getTotal());
    }

    public function test_add_and_subtract_keep_vat_percentage()
    {
        $vatTotal = VatTotal::make(VatPercentage::fromString('21'), Money::EUR(100));
        $updated = $vatTotal->add(Money::EUR(50))->subtract(Money::EUR(30));

        $this->assertEquals(VatPercentage::fromString('21'), $updated->getVatPercentage());
    }

    public function test_currency_must_match()
    {
        $vatTotal = VatTotal::make(VatPercentage::fromString('6'), Money::EUR(100));

        $this->expectException(\Money\Exception\CurrencyMismatchException::class);

        $vatTotal->add(Money::USD(10)); // Money library will throw here
    }
}
