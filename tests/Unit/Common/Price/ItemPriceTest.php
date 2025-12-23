<?php
declare(strict_types=1);

namespace Tests\Unit\Common\Price;

use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemPrice;
use Thinktomorrow\Trader\Domain\Common\Price\Exceptions\PriceCannotBeNegative;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

class ItemPriceTest extends TestCase
{
    public function test_it_can_make_price_including_vat()
    {
        $object = DefaultItemPrice::fromMoney(
            Money::EUR(120),
            VatPercentage::fromString('20'),
            true
        );

        $this->assertEquals(Money::EUR(120), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
    }

    public function test_it_can_make_price_excluding_vat()
    {
        $object = DefaultItemPrice::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        );

        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(120), $object->getIncludingVat());
    }

    public function test_it_can_multiply_a_price()
    {
        $object = DefaultItemPrice::fromMoney(
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
        $object = DefaultItemPrice::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        );

        $object = $object->changeVatPercentage(VatPercentage::fromString('6'));

        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(106), $object->getIncludingVat());

        // From price including vat
        $object = DefaultItemPrice::fromMoney(
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
        $object = DefaultItemPrice::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        );

        $this->assertEquals(VatPercentage::fromString('20'), $object->getVatPercentage());
        $this->assertEquals(Money::EUR(20), $object->getVatTotal());
    }

    public function test_it_allows_small_rounding_differences_in_vat_validation()
    {
        // 21% VAT on tricky number
        $object = DefaultItemPrice::fromMoney(
            Money::EUR(403),  // incl
            VatPercentage::fromString('21'),
            true
        );

        $otherObject = DefaultItemPrice::fromMoney(
            Money::EUR(333),  // excl
            VatPercentage::fromString('21'),
            false
        );

        $this->assertEquals(Money::EUR(403), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(403), $otherObject->getIncludingVat());
        $this->assertEquals(Money::EUR(333), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(333), $otherObject->getExcludingVat());
        $this->assertEquals(Money::EUR(70), $object->getVatTotal());
        $this->assertEquals(Money::EUR(70), $otherObject->getVatTotal());
    }

    public function test_change_vat_percentage_handles_rounding_edges()
    {
        $object = DefaultItemPrice::fromMoney(
            Money::EUR(121),
            VatPercentage::fromString('21'),
            true
        );

        $object = $object->changeVatPercentage(VatPercentage::fromString('6'));

        // 121 incl - 21% = 100 excl
        // 100 + 6% = 106 incl
        $this->assertEquals(Money::EUR(106), $object->getIncludingVat());
    }

    public function test_apply_discount_cannot_make_values_negative()
    {
        $object = DefaultItemPrice::fromMoney(
            Money::EUR(100),
            VatPercentage::fromString('20'),
            false
        );

        $discount = DefaultDiscountPrice::fromExcludingVat(Money::EUR(500));

        $this->expectException(PriceCannotBeNegative::class);

        $object->applyDiscount($discount);
    }

    #[DataProvider('quantityProvider')]
    public function test_it_avoids_rounding_errors_for_quantities(int $qty)
    {
        $item = DefaultItemPrice::fromMoney(
            Money::EUR(2521),
            VatPercentage::fromString('21'),
            false
        );

        $object = $item->multiply($qty);

        // Expected values calculated externally
        $excl = 2521 * $qty;
        $incl = (int)round($excl * 1.21);
        $vat = $incl - $excl;

        $this->assertEquals(Money::EUR($excl), $object->getExcludingVat());
        $this->assertEquals(Money::EUR($incl), $object->getIncludingVat());
        $this->assertEquals(Money::EUR($vat), $object->getVatTotal());
    }

    public static function quantityProvider(): array
    {
        return [
            [2], [3], [4], [5], [6], [7], [8], [9],
            [11], [13], [17], [19], [23], [29], [31],
            [37], [41], [43], [47], [53],
        ];
    }
}
