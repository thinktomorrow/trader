<?php
declare(strict_types=1);

namespace Tests\Unit\Common\Cash;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\PriceCannotBeNegative;
use Thinktomorrow\Trader\Domain\Common\Price\PriceCannotContainMultipleTaxRates;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

class PriceTest extends TestCase
{
    public function test_it_can_make_price_including_vat()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(120),
            VatPercentage::fromString('20'),
            true
        );

        $this->assertEquals(Money::EUR(120), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
    }

    public function test_it_can_make_price_excluding_vat()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        );

        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(120), $object->getIncludingVat());
    }

    public function test_it_can_add_a_price()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(120),
            VatPercentage::fromString('20'),
            true
        );

        $object = $object->add(PriceStub::fromMoney(
            Money::EUR(72),
            VatPercentage::fromString('20'),
            true
        ));

        $this->assertEquals(Money::EUR(160), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(192), $object->getIncludingVat());
    }

    public function test_it_can_subtract_a_price()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(120),
            VatPercentage::fromString('20'),
            true
        );

        $object = $object->subtract(PriceStub::fromMoney(
            Money::EUR(72),
            VatPercentage::fromString('20'),
            true
        ));

        $this->assertEquals(Money::EUR(40), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(48), $object->getIncludingVat());
    }

    public function test_it_can_multiply_a_price()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        );

        $this->assertEquals(Money::EUR(200), $object->multiply(2)->getExcludingVat());
        $this->assertEquals(Money::EUR(240), $object->multiply(2)->getIncludingVat());
    }

    public function test_it_can_change_vatrate()
    {
        // From price excluding vat
        $object = PriceStub::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        );

        $object = $object->changeVatPercentage(VatPercentage::fromString('6'));

        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(106), $object->getIncludingVat());

        // From price including vat
        $object = PriceStub::fromMoney(
            Money::EUR(120),
            VatPercentage::fromString('20'),
            true
        );

        $object = $object->changeVatPercentage(VatPercentage::fromString('6'));

        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(106), $object->getIncludingVat());
    }

    public function test_it_can_get_vat_total()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        );

        $this->assertEquals(VatPercentage::fromString('20'), $object->getVatPercentage());
        $this->assertEquals(Money::EUR(20), $object->getVatTotal());
    }

    public function test_it_can_subtract_to_zero()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        );

        $object = $object->subtract(PriceStub::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        ));

        $this->assertEquals(Money::EUR(0), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(0), $object->getIncludingVat());
    }

    public function test_it_cannot_be_negative()
    {
        $this->expectException(PriceCannotBeNegative::class);

        $object = PriceStub::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        );

        $object->subtract(PriceStub::fromMoney(
            Money::EUR(101),
            VatPercentage::fromString('20'),
            false
        ));
    }

    public function test_it_cannot_contain_multiple_vat_rates_when_subtracting()
    {
        $this->expectException(PriceCannotContainMultipleTaxRates::class);

        $object = PriceStub::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        );

        $object->subtract(PriceStub::fromMoney(
            Money::EUR(80),
            VatPercentage::fromString('6'),
            false
        ));
    }

    public function test_it_cannot_contain_multiple_vat_rates_when_adding_up()
    {
        $this->expectException(PriceCannotContainMultipleTaxRates::class);

        $object = PriceStub::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        );

        $object->add(PriceStub::fromMoney(
            Money::EUR(80),
            VatPercentage::fromString('6'),
            false
        ));
    }

    public function test_it_can_subtract_with_different_vat_inclusions()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        );

        $object = $object->subtract(PriceStub::fromMoney(
            Money::EUR(72),
            VatPercentage::fromString('20'),
            true // 60 excl.
        ));

        $this->assertEquals(Money::EUR(40), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(48), $object->getIncludingVat());
    }

    public function test_it_can_add_with_different_vat_inclusions()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        );

        $object = $object->add(PriceStub::fromMoney(
            Money::EUR(72),
            VatPercentage::fromString('20'),
            true
        ));

        $this->assertEquals(Money::EUR(160), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(192), $object->getIncludingVat());
    }

    public function test_it_can_force_subtracting_with_different_vat_rate()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        );

        $object = $object->subtractDifferent(PriceStub::fromMoney(
            Money::EUR(80),
            VatPercentage::fromString('6'),
            false
        ));

        $this->assertEquals(VatPercentage::fromString('20'), $object->getVatPercentage());
        $this->assertEquals(Money::EUR(20), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(24), $object->getIncludingVat());
    }

    public function test_it_can_force_adding_with_different_vat_rate()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        );

        $object = $object->addDifferent(PriceStub::fromMoney(
            Money::EUR(80),
            VatPercentage::fromString('6'),
            false
        ));

        $this->assertEquals(VatPercentage::fromString('20'), $object->getVatPercentage());
        $this->assertEquals(Money::EUR(180), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(216), $object->getIncludingVat()); // 120 + 96 ( = 80 with vat rate 20%)
    }
}
