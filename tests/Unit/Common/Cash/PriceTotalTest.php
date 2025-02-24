<?php
declare(strict_types=1);

namespace Tests\Unit\Common\Cash;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\PriceCannotBeNegative;
use Thinktomorrow\Trader\Domain\Common\Vat\VatApplicableTotal;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

class PriceTotalTest extends TestCase
{
    public function test_it_can_make_price_total()
    {
        $object = PriceTotalStub::zero();
        $object = $object->add(PriceStub::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        ));

        $this->assertEquals(Money::EUR(120), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(20), $object->getVatTotals()->getVatTotal());
    }

    public function test_it_can_make_price_total_including_tax()
    {
        $object = PriceTotalStub::zero();
        $object = $object->add(PriceStub::fromMoney(
            Money::EUR(32200),
            VatPercentage::fromString('21'),
            true
        ));

        $this->assertEquals(Money::EUR(32200), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(26612), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(26612), $object->getVatTotals()->getVatApplicableTotal());
        $this->assertEquals(Money::EUR(32200 - 26612), $object->getVatTotals()->getVatTotal());
    }

    public function test_it_can_make_total_of_multiple_prices()
    {
        $object = PriceTotalStub::zero();
        $object = $object->add(PriceStub::fromMoney(Money::EUR(100), VatPercentage::fromString('20'), false));
        $object = $object->add(PriceStub::fromMoney(Money::EUR(100), VatPercentage::fromString('20'), false));
        $object = $object->add(PriceStub::fromMoney(Money::EUR(106), VatPercentage::fromString('6'), true));

        $this->assertEquals(Money::EUR(346), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(300), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(46), $object->getVatTotals()->getVatTotal());

        $this->assertEquals(VatApplicableTotal::calculateFromMoney(Money::EUR(200)), $object->getVatTotals()->find(VatPercentage::fromString('20'))->getVatApplicableTotal());
        $this->assertEquals(Money::EUR(40), $object->getVatTotals()->find(VatPercentage::fromString('20'))->getVatTotal());
        $this->assertEquals(VatApplicableTotal::calculateFromMoney(Money::EUR(100)), $object->getVatTotals()->find(VatPercentage::fromString('6'))->getVatApplicableTotal());
        $this->assertEquals(Money::EUR(100), $object->getVatTotals()->find(VatPercentage::fromString('6'))->getVatApplicableTotal()->getMoney());
        $this->assertEquals(Money::EUR(6), $object->getVatTotals()->find(VatPercentage::fromString('6'))->getVatTotal());
    }

    public function test_it_can_subtract_price_from_total()
    {
        $object = PriceTotalStub::zero();
        $object = $object->add(PriceStub::fromMoney(Money::EUR(100), VatPercentage::fromString('20'), false));
        $object = $object->add(PriceStub::fromMoney(Money::EUR(100), VatPercentage::fromString('20'), false));
        $object = $object->add(PriceStub::fromMoney(Money::EUR(106), VatPercentage::fromString('6'), true));

        $this->assertEquals(Money::EUR(346), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(300), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(46), $object->getVatTotals()->getVatTotal());

        $object = $object->subtract(PriceStub::fromMoney(Money::EUR(120), VatPercentage::fromString('20'), true));

        $this->assertEquals(Money::EUR(226), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(200), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(26), $object->getVatTotals()->getVatTotal());
    }

    public function test_it_can_subtract_price_from_total_with_price_including_different_taxrate()
    {
        $object = PriceTotalStub::zero();
        $object = $object->add(PriceStub::fromMoney(Money::EUR(100), VatPercentage::fromString('20'), false));
        $object = $object->add(PriceStub::fromMoney(Money::EUR(100), VatPercentage::fromString('20'), false));
        $object = $object->add(PriceStub::fromMoney(Money::EUR(106), VatPercentage::fromString('6'), true));

        $this->assertEquals(Money::EUR(346), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(300), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(46), $object->getVatTotals()->getVatTotal());

        $object = $object->subtract(PriceStub::fromMoney(Money::EUR(110), VatPercentage::fromString('10'), true));

        $this->assertEquals(Money::EUR(236), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(200), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(36), $object->getVatTotals()->getVatTotal());
    }

    public function test_it_can_subtract_price_to_zero()
    {
        $object = PriceTotalStub::zero();
        $object = $object->add(PriceStub::fromMoney(Money::EUR(100), VatPercentage::fromString('20'), false));

        $this->assertEquals(Money::EUR(120), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(20), $object->getVatTotals()->getVatTotal());

        $object = $object->subtract(PriceStub::fromMoney(Money::EUR(120), VatPercentage::fromString('20'), true));

        $this->assertEquals(Money::EUR(0), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(0), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(0), $object->getVatTotals()->getVatTotal());
    }

    public function test_it_cannot_subtract_price_from_total_below_zero()
    {
        $this->expectException(PriceCannotBeNegative::class);

        $object = PriceTotalStub::zero();
        $object = $object->add(PriceStub::fromMoney(Money::EUR(100), VatPercentage::fromString('20'), false));

        $object = $object->subtract(PriceStub::fromMoney(Money::EUR(1200), VatPercentage::fromString('20'), true));
    }
}
