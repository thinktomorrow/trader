<?php

namespace Tests\Unit\Common\Price;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\Exceptions\PriceCannotBeNegative;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

final class ItemDiscountPriceTest extends TestCase
{
    public function test_it_can_create_discount_from_excluding_vat(): void
    {
        $discount = DefaultItemDiscountPrice::fromExcludingVat(
            Money::EUR(100),
            VatPercentage::fromString('21')
        );

        $this->assertEquals(Money::EUR(100), $discount->getExcludingVat());
        $this->assertEquals(Money::EUR(121), $discount->getIncludingVat());
        $this->assertFalse($discount->isIncludingVatAuthoritative());
    }

    public function test_it_can_create_discount_from_including_vat(): void
    {
        $discount = DefaultItemDiscountPrice::fromIncludingVat(
            Money::EUR(121),
            VatPercentage::fromString('21')
        );

        $this->assertEquals(Money::EUR(100), $discount->getExcludingVat());
        $this->assertEquals(Money::EUR(121), $discount->getIncludingVat());
        $this->assertTrue($discount->isIncludingVatAuthoritative());
    }

    public function test_it_can_create_zero_discount(): void
    {
        $discount = DefaultItemDiscountPrice::zero(VatPercentage::fromString('21'));

        $this->assertEquals(Money::EUR(0), $discount->getExcludingVat());
        $this->assertEquals(Money::EUR(0), $discount->getIncludingVat());
        $this->assertFalse($discount->isIncludingVatAuthoritative());
    }

    public function test_it_can_create_zero_discount_with_including_vat_authoritative(): void
    {
        $discount = DefaultItemDiscountPrice::zero(VatPercentage::fromString('21'), true);

        $this->assertEquals(Money::EUR(0), $discount->getExcludingVat());
        $this->assertEquals(Money::EUR(0), $discount->getIncludingVat());
        $this->assertTrue($discount->isIncludingVatAuthoritative());
    }

    public function test_it_can_add_discounts_excluding_vat(): void
    {
        $discountA = DefaultItemDiscountPrice::fromExcludingVat(
            Money::EUR(100),
            VatPercentage::fromString('21')
        );

        $discountB = DefaultItemDiscountPrice::fromExcludingVat(
            Money::EUR(50),
            VatPercentage::fromString('21')
        );

        $result = $discountA->add($discountB);

        $this->assertEquals(Money::EUR(150), $result->getExcludingVat());
        $this->assertEquals(Money::EUR(182), $result->getIncludingVat());
        $this->assertFalse($result->isIncludingVatAuthoritative());
    }

    public function test_it_can_add_discounts_including_vat_authoritative(): void
    {
        $discountA = DefaultItemDiscountPrice::fromIncludingVat(
            Money::EUR(121),
            VatPercentage::fromString('21')
        );

        $discountB = DefaultItemDiscountPrice::fromIncludingVat(
            Money::EUR(60),
            VatPercentage::fromString('21')
        );

        $result = $discountA->add($discountB);

        $this->assertEquals(Money::EUR(181), $result->getIncludingVat());
        $this->assertTrue($result->isIncludingVatAuthoritative());
    }

    public function test_addition_is_immutable(): void
    {
        $discountA = DefaultItemDiscountPrice::fromExcludingVat(
            Money::EUR(100),
            VatPercentage::fromString('21')
        );

        $discountB = DefaultItemDiscountPrice::fromExcludingVat(
            Money::EUR(50),
            VatPercentage::fromString('21')
        );

        $result = $discountA->add($discountB);

        $this->assertEquals(Money::EUR(100), $discountA->getExcludingVat());
        $this->assertEquals(Money::EUR(50), $discountB->getExcludingVat());
        $this->assertEquals(Money::EUR(150), $result->getExcludingVat());
    }

    public function test_it_does_not_allow_negative_discount_on_creation(): void
    {
        $this->expectException(PriceCannotBeNegative::class);

        DefaultItemDiscountPrice::fromExcludingVat(
            Money::EUR(-1),
            VatPercentage::fromString('21')
        );
    }

    public function test_it_does_not_allow_negative_discount_after_addition(): void
    {
        $this->expectException(PriceCannotBeNegative::class);

        $discountA = DefaultItemDiscountPrice::fromExcludingVat(
            Money::EUR(50),
            VatPercentage::fromString('21')
        );

        $discountB = DefaultItemDiscountPrice::fromExcludingVat(
            Money::EUR(-100),
            VatPercentage::fromString('21')
        );

        $discountA->add($discountB);
    }

    public function test_multiply_excluding_vat(): void
    {
        $discount = DefaultItemDiscountPrice::fromExcludingVat(
            Money::EUR(10),
            VatPercentage::fromString('21')
        );

        $result = $discount->multiply(3);

        $this->assertEquals(Money::EUR(30), $result->getExcludingVat());
        $this->assertEquals(Money::EUR(36), $result->getIncludingVat());
    }

    public function test_multiply_including_vat_authoritative(): void
    {
        $discount = DefaultItemDiscountPrice::fromIncludingVat(
            Money::EUR(12),
            VatPercentage::fromString('21')
        );

        $result = $discount->multiply(3);

        $this->assertEquals(Money::EUR(36), $result->getIncludingVat());
        $this->assertTrue($result->isIncludingVatAuthoritative());
    }

    public function test_multiple_additions_accumulate_correctly(): void
    {
        $discount = DefaultItemDiscountPrice::zero(VatPercentage::fromString('21'));

        $discount = $discount->add(
            DefaultItemDiscountPrice::fromExcludingVat(Money::EUR(10), VatPercentage::fromString('21'))
        );
        $discount = $discount->add(
            DefaultItemDiscountPrice::fromExcludingVat(Money::EUR(20), VatPercentage::fromString('21'))
        );
        $discount = $discount->add(
            DefaultItemDiscountPrice::fromExcludingVat(Money::EUR(30), VatPercentage::fromString('21'))
        );

        $this->assertEquals(Money::EUR(60), $discount->getExcludingVat());
        $this->assertEquals(Money::EUR(73), $discount->getIncludingVat());
    }

    public function test_large_discount_values_are_supported(): void
    {
        $discount = DefaultItemDiscountPrice::fromExcludingVat(
            Money::EUR(1_000_000),
            VatPercentage::fromString('21')
        );

        $this->assertEquals(Money::EUR(1_000_000), $discount->getExcludingVat());
    }
}
