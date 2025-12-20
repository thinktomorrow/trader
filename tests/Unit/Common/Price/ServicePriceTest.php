<?php

namespace Tests\Unit\Common\Price;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultServicePrice;
use Thinktomorrow\Trader\Domain\Common\Price\Exceptions\PriceCannotBeNegative;

final class ServicePriceTest extends TestCase
{
    public function test_it_can_create_service_price_from_excluding_vat(): void
    {
        $price = DefaultServicePrice::fromExcludingVat(Money::EUR(100));

        $this->assertEquals(Money::EUR(100), $price->getExcludingVat());
    }

    public function test_it_does_not_allow_negative_excluding_vat_on_creation(): void
    {
        $this->expectException(PriceCannotBeNegative::class);

        DefaultServicePrice::fromExcludingVat(Money::EUR(-1));
    }

    public function test_it_can_apply_discount(): void
    {
        $price = DefaultServicePrice::fromExcludingVat(Money::EUR(200));
        $discount = DefaultDiscountPrice::fromExcludingVat(Money::EUR(50));

        $result = $price->applyDiscount($discount);

        $this->assertEquals(Money::EUR(150), $result->getExcludingVat());
    }

    public function test_apply_discount_is_immutable(): void
    {
        $price = DefaultServicePrice::fromExcludingVat(Money::EUR(200));
        $discount = DefaultDiscountPrice::fromExcludingVat(Money::EUR(50));

        $result = $price->applyDiscount($discount);

        // original unchanged
        $this->assertEquals(Money::EUR(200), $price->getExcludingVat());
        $this->assertEquals(Money::EUR(150), $result->getExcludingVat());
    }

    public function test_applying_zero_discount_changes_nothing(): void
    {
        $price = DefaultServicePrice::fromExcludingVat(Money::EUR(123));
        $discount = DefaultDiscountPrice::zero();

        $result = $price->applyDiscount($discount);

        $this->assertEquals(Money::EUR(123), $result->getExcludingVat());
    }

    public function test_it_does_not_allow_discount_to_make_price_negative(): void
    {
        $this->expectException(PriceCannotBeNegative::class);

        $price = DefaultServicePrice::fromExcludingVat(Money::EUR(100));
        $discount = DefaultDiscountPrice::fromExcludingVat(Money::EUR(200));

        $price->applyDiscount($discount);
    }

    public function test_large_service_prices_are_supported(): void
    {
        $price = DefaultServicePrice::fromExcludingVat(Money::EUR(1_000_000));

        $this->assertEquals(Money::EUR(1_000_000), $price->getExcludingVat());
    }
}

