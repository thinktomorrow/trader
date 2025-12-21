<?php

namespace Tests\Unit\Common\Price;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\Exceptions\PriceCannotBeNegative;

final class DiscountPriceTest extends TestCase
{
    public function test_it_can_create_discount_from_excluding_vat(): void
    {
        $discount = DefaultDiscountPrice::fromExcludingVat(Money::EUR(100));

        $this->assertEquals(Money::EUR(100), $discount->getExcludingVat());
    }

    public function test_it_can_create_zero_discount(): void
    {
        $discount = DefaultDiscountPrice::zero();

        $this->assertEquals(Money::EUR(0), $discount->getExcludingVat());
    }

    public function test_it_can_add_discounts(): void
    {
        $discountA = DefaultDiscountPrice::fromExcludingVat(Money::EUR(100));
        $discountB = DefaultDiscountPrice::fromExcludingVat(Money::EUR(50));

        $result = $discountA->add($discountB);

        $this->assertEquals(Money::EUR(150), $result->getExcludingVat());
    }

    public function test_addition_is_immutable(): void
    {
        $discountA = DefaultDiscountPrice::fromExcludingVat(Money::EUR(100));
        $discountB = DefaultDiscountPrice::fromExcludingVat(Money::EUR(50));

        $result = $discountA->add($discountB);

        // originals unchanged
        $this->assertEquals(Money::EUR(100), $discountA->getExcludingVat());
        $this->assertEquals(Money::EUR(50), $discountB->getExcludingVat());
        $this->assertEquals(Money::EUR(150), $result->getExcludingVat());
    }

    public function test_it_does_not_allow_negative_discount_on_creation(): void
    {
        $this->expectException(PriceCannotBeNegative::class);

        DefaultDiscountPrice::fromExcludingVat(Money::EUR(-1));
    }

    public function test_it_does_not_allow_negative_discount_after_addition(): void
    {
        $this->expectException(PriceCannotBeNegative::class);

        $discountA = DefaultDiscountPrice::fromExcludingVat(Money::EUR(50));
        $discountB = DefaultDiscountPrice::fromExcludingVat(Money::EUR(-100));

        $discountA->add($discountB);
    }

    public function test_adding_zero_discount_changes_nothing(): void
    {
        $discount = DefaultDiscountPrice::fromExcludingVat(Money::EUR(123));

        $result = $discount->add(DefaultDiscountPrice::zero());

        $this->assertEquals(Money::EUR(123), $result->getExcludingVat());
    }

    public function test_multiple_additions_accumulate_correctly(): void
    {
        $discount = DefaultDiscountPrice::zero();

        $discount = $discount->add(DefaultDiscountPrice::fromExcludingVat(Money::EUR(10)));
        $discount = $discount->add(DefaultDiscountPrice::fromExcludingVat(Money::EUR(20)));
        $discount = $discount->add(DefaultDiscountPrice::fromExcludingVat(Money::EUR(30)));

        $this->assertEquals(Money::EUR(60), $discount->getExcludingVat());
    }

    public function test_large_discount_values_are_supported(): void
    {
        $discount = DefaultDiscountPrice::fromExcludingVat(Money::EUR(1_000_000));

        $this->assertEquals(Money::EUR(1_000_000), $discount->getExcludingVat());
    }
}

