<?php
declare(strict_types=1);

namespace Tests\Unit\Common\Price;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultTotalPrice;
use Thinktomorrow\Trader\Domain\Common\Price\Exceptions\PriceCannotBeNegative;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

class TotalPriceTest extends TestCase
{
    public function test_it_can_make_price_total()
    {
        $object = DefaultTotalPrice::zero();
        $object = $object->add(DefaultItemPrice::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        ));

//        $this->assertEquals(Money::EUR(120), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
//        $this->assertEquals(Money::EUR(20), $object->getVatTotal());
    }

    public function test_it_can_make_price_total_including_tax()
    {
        $object = DefaultTotalPrice::zero();
        $object = $object->add(DefaultItemPrice::fromMoney(
            Money::EUR(32200),
            VatPercentage::fromString('21'),
            true
        ));

//        $this->assertEquals(Money::EUR(32200), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(26612), $object->getExcludingVat());
//        $this->assertEquals(Money::EUR(32200 - 26612), $object->getVatTotal());
    }

    public function test_it_can_avoid_rounding_errors()
    {
        $object = DefaultTotalPrice::zero();
        $object = $object->add(DefaultItemPrice::fromMoney(Money::EUR(2521), VatPercentage::fromString('21'), false)->multiply(2));

//        $this->assertEquals(Money::EUR(6101), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(5042), $object->getExcludingVat());
//        $this->assertEquals(Money::EUR(1059), $object->getVatTotal());
    }

    public function test_it_can_make_total_of_multiple_vat_percentages()
    {
        $object = DefaultTotalPrice::zero();
        $object = $object->add(DefaultItemPrice::fromMoney(Money::EUR(100), VatPercentage::fromString('20'), false));
        $object = $object->add(DefaultItemPrice::fromMoney(Money::EUR(100), VatPercentage::fromString('20'), false));
        $object = $object->add(DefaultItemPrice::fromMoney(Money::EUR(106), VatPercentage::fromString('6'), true));

//        $this->assertEquals(Money::EUR(346), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(300), $object->getExcludingVat());
//        $this->assertEquals(Money::EUR(46), $object->getVatTotal());
    }

    public function test_it_can_subtract_price_from_total()
    {
        $object = DefaultTotalPrice::zero();
        $object = $object->add(DefaultItemPrice::fromMoney(Money::EUR(100), VatPercentage::fromString('20'), false));
        $object = $object->add(DefaultItemPrice::fromMoney(Money::EUR(100), VatPercentage::fromString('20'), false));
        $object = $object->add(DefaultItemPrice::fromMoney(Money::EUR(106), VatPercentage::fromString('6'), true));

//        $this->assertEquals(Money::EUR(346), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(300), $object->getExcludingVat());
//        $this->assertEquals(Money::EUR(46), $object->getVatTotal());

        $object = $object->subtract(DefaultItemPrice::fromMoney(Money::EUR(120), VatPercentage::fromString('20'), true));

//        $this->assertEquals(Money::EUR(226), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(200), $object->getExcludingVat());
//        $this->assertEquals(Money::EUR(26), $object->getVatTotal());
    }

    public function test_it_can_subtract_price_from_total_with_price_including_different_taxrate()
    {
        $object = DefaultTotalPrice::zero();
        $object = $object->add(DefaultItemPrice::fromMoney(Money::EUR(100), VatPercentage::fromString('20'), false));
        $object = $object->add(DefaultItemPrice::fromMoney(Money::EUR(100), VatPercentage::fromString('20'), false));
        $object = $object->add(DefaultItemPrice::fromMoney(Money::EUR(106), VatPercentage::fromString('6'), true));

//        $this->assertEquals(Money::EUR(346), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(300), $object->getExcludingVat());
//        $this->assertEquals(Money::EUR(46), $object->getVatTotal());

        $object = $object->subtract(DefaultItemPrice::fromMoney(Money::EUR(110), VatPercentage::fromString('10'), true));

        $this->assertEquals(Money::EUR(200), $object->getExcludingVat());
//        $this->assertEquals(Money::EUR(36), $object->getVatTotal());
    }

    public function test_it_can_subtract_price_to_zero()
    {
        $object = DefaultTotalPrice::zero();
        $object = $object->add(DefaultItemPrice::fromMoney(Money::EUR(100), VatPercentage::fromString('20'), false));

        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
//        $this->assertEquals(Money::EUR(20), $object->getVatTotal());

        $object = $object->subtract(DefaultItemPrice::fromMoney(Money::EUR(120), VatPercentage::fromString('20'), true));

//        $this->assertEquals(Money::EUR(0), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(0), $object->getExcludingVat());
//        $this->assertEquals(Money::EUR(0), $object->getVatTotal());
    }

    public function test_it_cannot_subtract_price_from_total_below_zero()
    {
        $this->expectException(PriceCannotBeNegative::class);

        $object = DefaultTotalPrice::zero();
        $object = $object->add(DefaultItemPrice::fromMoney(Money::EUR(100), VatPercentage::fromString('20'), false));

        $object = $object->subtract(DefaultItemPrice::fromMoney(Money::EUR(1200), VatPercentage::fromString('20'), true));
    }

    public function test_it_cannot_add_prices_in_different_currencies()
    {
        $this->expectException(\Money\Exception\CurrencyMismatchException::class);

        $object = DefaultTotalPrice::zero();
        $object->add(DefaultItemPrice::fromMoney(
            Money::USD(100),
            VatPercentage::fromString('20'),
            false
        ));
    }

    public function test_it_cannot_subtract_prices_in_different_currencies()
    {
        $this->expectException(\Money\Exception\CurrencyMismatchException::class);

        $object = DefaultTotalPrice::zero();
        $object = $object->add(DefaultItemPrice::fromMoney(Money::EUR(100), VatPercentage::fromString('20'), false));

        $object->subtract(DefaultItemPrice::fromMoney(Money::USD(120), VatPercentage::fromString('20'), true));
    }

    public function test_subtracting_larger_excluding_than_including_throws()
    {
        $this->expectException(PriceCannotBeNegative::class);

        $object = DefaultTotalPrice::fromExcludingVat(Money::EUR(150));

        $removal = DefaultTotalPrice::fromExcludingVat(Money::EUR(200));

        $object->subtract($removal);
    }
}
