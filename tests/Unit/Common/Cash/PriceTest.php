<?php
declare(strict_types=1);

namespace Tests\Unit\Common\Cash;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceCannotBeNegative;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceCannotContainMultipleTaxRates;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;

class PriceTest extends TestCase
{
    /** @test */
    public function it_can_make_price_including_vat()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(120),
            TaxRate::fromString('20'),
            true
        );

        $this->assertEquals(Money::EUR(120), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
    }

    /** @test */
    public function it_can_make_price_excluding_vat()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(100),
            TaxRate::fromString('20'),
            false
        );

        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(120), $object->getIncludingVat());
    }

    /** @test */
    public function it_can_add_a_price()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(120),
            TaxRate::fromString('20'),
            true
        );

        $object = $object->add(PriceStub::fromMoney(
            Money::EUR(72),
            TaxRate::fromString('20'),
            true
        ));

        $this->assertEquals(Money::EUR(160), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(192), $object->getIncludingVat());
    }

    /** @test */
    public function it_can_subtract_a_price()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(120),
            TaxRate::fromString('20'),
            true
        );

        $object = $object->subtract(PriceStub::fromMoney(
            Money::EUR(72),
            TaxRate::fromString('20'),
            true
        ));

        $this->assertEquals(Money::EUR(40), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(48), $object->getIncludingVat());
    }

    /** @test */
    public function it_can_multiply_a_price()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(100),
            TaxRate::fromString('20'),
            false
        );

        $this->assertEquals(Money::EUR(200), $object->multiply(2)->getExcludingVat());
        $this->assertEquals(Money::EUR(240), $object->multiply(2)->getIncludingVat());
    }

    /** @test */
    public function it_can_change_taxrate()
    {
        // From price excluding vat
        $object = PriceStub::fromMoney(
            Money::EUR(100),
            TaxRate::fromString('20'),
            false
        );

        $object = $object->changeTaxRate(TaxRate::fromString('6'));

        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(106), $object->getIncludingVat());

        // From price including vat
        $object = PriceStub::fromMoney(
            Money::EUR(120),
            TaxRate::fromString('20'),
            true
        );

        $object = $object->changeTaxRate(TaxRate::fromString('6'));

        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(106), $object->getIncludingVat());
    }

    /** @test */
    public function it_can_get_tax_total()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(100),
            TaxRate::fromString('20'),
            false
        );

        $this->assertEquals(TaxRate::fromString('20'), $object->getTaxRate());
        $this->assertEquals(Money::EUR(20), $object->getTaxTotal());
    }

    /** @test */
    public function it_can_subtract_to_zero()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(100),
            TaxRate::fromString('20'),
            false
        );

        $object = $object->subtract(PriceStub::fromMoney(
            Money::EUR(100),
            TaxRate::fromString('20'),
            false
        ));

        $this->assertEquals(Money::EUR(0), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(0), $object->getIncludingVat());
    }

    /** @test */
    public function it_cannot_be_negative()
    {
        $this->expectException(PriceCannotBeNegative::class);

        $object = PriceStub::fromMoney(
            Money::EUR(100),
            TaxRate::fromString('20'),
            false
        );

        $object->subtract(PriceStub::fromMoney(
            Money::EUR(101),
            TaxRate::fromString('20'),
            false
        ));
    }

    /** @test */
    public function it_cannot_contain_multiple_tax_rates_when_subtracting()
    {
        $this->expectException(PriceCannotContainMultipleTaxRates::class);

        $object = PriceStub::fromMoney(
            Money::EUR(100),
            TaxRate::fromString('20'),
            false
        );

        $object->subtract(PriceStub::fromMoney(
            Money::EUR(80),
            TaxRate::fromString('6'),
            false
        ));
    }

    /** @test */
    public function it_cannot_contain_multiple_tax_rates_when_adding_up()
    {
        $this->expectException(PriceCannotContainMultipleTaxRates::class);

        $object = PriceStub::fromMoney(
            Money::EUR(100),
            TaxRate::fromString('20'),
            false
        );

        $object->add(PriceStub::fromMoney(
            Money::EUR(80),
            TaxRate::fromString('6'),
            false
        ));
    }

    /** @test */
    public function it_can_subtract_with_different_tax_inclusions()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(100),
            TaxRate::fromString('20'),
            false
        );

        $object = $object->subtract(PriceStub::fromMoney(
            Money::EUR(72),
            TaxRate::fromString('20'),
            true // 60 excl.
        ));

        $this->assertEquals(Money::EUR(40), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(48), $object->getIncludingVat());
    }

    /** @test */
    public function it_can_add_with_different_tax_inclusions()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(100),
            TaxRate::fromString('20'),
            false
        );

        $object = $object->add(PriceStub::fromMoney(
            Money::EUR(72),
            TaxRate::fromString('20'),
            true
        ));

        $this->assertEquals(Money::EUR(160), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(192), $object->getIncludingVat());
    }

    /** @test */
    public function it_can_force_subtracting_with_different_tax_rate()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(100),
            TaxRate::fromString('20'),
            false
        );

        $object = $object->subtractDifferent(PriceStub::fromMoney(
            Money::EUR(80),
            TaxRate::fromString('6'),
            false
        ));

        $this->assertEquals(TaxRate::fromString('20'), $object->getTaxRate());
        $this->assertEquals(Money::EUR(20), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(24), $object->getIncludingVat());
    }

    /** @test */
    public function it_can_force_adding_with_different_tax_rate()
    {
        $object = PriceStub::fromMoney(
            Money::EUR(100),
            TaxRate::fromString('20'),
            false
        );

        $object = $object->addDifferent(PriceStub::fromMoney(
            Money::EUR(80),
            TaxRate::fromString('6'),
            false
        ));

        $this->assertEquals(TaxRate::fromString('20'), $object->getTaxRate());
        $this->assertEquals(Money::EUR(180), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(216), $object->getIncludingVat()); // 120 + 96 ( = 80 with taxrate 20%)
    }
}
