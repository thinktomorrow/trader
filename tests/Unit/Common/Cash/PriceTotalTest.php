<?php
declare(strict_types=1);

namespace Tests\Unit\Common\Cash;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\PriceCannotBeNegative;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;

class PriceTotalTest extends TestCase
{
    /** @test */
    public function it_can_make_price_total()
    {
        $object = PriceTotalStub::zero();
        $object = $object->add(PriceStub::fromMoney(
            Money::EUR(100),
            TaxRate::fromString('20'),
            false
        ));

        $this->assertEquals(Money::EUR(120), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(20), $object->getTaxRateTotals()->getTaxTotal());
    }

    /** @test */
    public function it_can_make_total_of_multiple_prices()
    {
        $object = PriceTotalStub::zero();
        $object = $object->add(PriceStub::fromMoney(Money::EUR(100), TaxRate::fromString('20'), false));
        $object = $object->add(PriceStub::fromMoney(Money::EUR(100), TaxRate::fromString('20'), false));
        $object = $object->add(PriceStub::fromMoney(Money::EUR(106), TaxRate::fromString('6'), true));

        $this->assertEquals(Money::EUR(346), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(300), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(46), $object->getTaxRateTotals()->getTaxTotal());

        $this->assertEquals(Money::EUR(200), $object->getTaxRateTotals()->find(TaxRate::fromString('20'))->getTaxableTotal());
        $this->assertEquals(Money::EUR(40), $object->getTaxRateTotals()->find(TaxRate::fromString('20'))->getTaxTotal());
        $this->assertEquals(Money::EUR(100), $object->getTaxRateTotals()->find(TaxRate::fromString('6'))->getTaxableTotal());
        $this->assertEquals(Money::EUR(6), $object->getTaxRateTotals()->find(TaxRate::fromString('6'))->getTaxTotal());
    }

    /** @test */
    public function it_can_subtract_price_from_total()
    {
        $object = PriceTotalStub::zero();
        $object = $object->add(PriceStub::fromMoney(Money::EUR(100), TaxRate::fromString('20'), false));
        $object = $object->add(PriceStub::fromMoney(Money::EUR(100), TaxRate::fromString('20'), false));
        $object = $object->add(PriceStub::fromMoney(Money::EUR(106), TaxRate::fromString('6'), true));

        $this->assertEquals(Money::EUR(346), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(300), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(46), $object->getTaxRateTotals()->getTaxTotal());

        $object = $object->subtract(PriceStub::fromMoney(Money::EUR(120), TaxRate::fromString('20'), true));

        $this->assertEquals(Money::EUR(226), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(200), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(26), $object->getTaxRateTotals()->getTaxTotal());
    }

    /** @test */
    public function it_can_subtract_price_from_total_with_price_including_different_taxrate()
    {
        $object = PriceTotalStub::zero();
        $object = $object->add(PriceStub::fromMoney(Money::EUR(100), TaxRate::fromString('20'), false));
        $object = $object->add(PriceStub::fromMoney(Money::EUR(100), TaxRate::fromString('20'), false));
        $object = $object->add(PriceStub::fromMoney(Money::EUR(106), TaxRate::fromString('6'), true));

        $this->assertEquals(Money::EUR(346), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(300), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(46), $object->getTaxRateTotals()->getTaxTotal());

        $object = $object->subtract(PriceStub::fromMoney(Money::EUR(110), TaxRate::fromString('10'), true));

        $this->assertEquals(Money::EUR(236), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(200), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(36), $object->getTaxRateTotals()->getTaxTotal());
    }

    /** @test */
    public function it_can_subtract_price_to_zero()
    {
        $object = PriceTotalStub::zero();
        $object = $object->add(PriceStub::fromMoney(Money::EUR(100), TaxRate::fromString('20'), false));

        $this->assertEquals(Money::EUR(120), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(20), $object->getTaxRateTotals()->getTaxTotal());

        $object = $object->subtract(PriceStub::fromMoney(Money::EUR(120), TaxRate::fromString('20'), true));

        $this->assertEquals(Money::EUR(0), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(0), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(0), $object->getTaxRateTotals()->getTaxTotal());
    }

    /** @test */
    public function it_cannot_subtract_price_from_total_below_zero()
    {
        $this->expectException(PriceCannotBeNegative::class);

        $object = PriceTotalStub::zero();
        $object = $object->add(PriceStub::fromMoney(Money::EUR(100), TaxRate::fromString('20'), false));

        $object = $object->subtract(PriceStub::fromMoney(Money::EUR(1200), TaxRate::fromString('20'), true));
    }
}
