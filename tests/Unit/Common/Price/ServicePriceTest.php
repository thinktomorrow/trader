<?php

namespace Tests\Unit\Common\Price;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultServicePrice;
use Thinktomorrow\Trader\Domain\Common\Price\Exceptions\PriceCannotBeNegative;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Common\Price\VatApplicableAmount;

final class ServicePriceTest extends TestCase
{
    public function test_it_preserves_an_authoritative_including_vat_amount_after_resolution(): void
    {
        $price = DefaultServicePrice::fromIncludingVat(Money::EUR(700), Money::EUR(579));

        $this->assertEquals(Money::EUR(579), $price->getExcludingVat());
        $this->assertEquals(Money::EUR(700), $price->getAuthoritativeAmount());
        $this->assertSame(TaxMode::Inclusive, $price->getTaxMode());
    }

    public function test_it_can_create_service_price_from_excluding_vat(): void
    {
        $price = DefaultServicePrice::fromExcludingVat(Money::EUR(100));

        $this->assertEquals(Money::EUR(100), $price->getExcludingVat());
    }

    public function test_it_creates_an_excluding_vat_service_price_from_a_vat_applicable_amount(): void
    {
        $price = DefaultServicePrice::fromVatApplicableAmount(
            VatApplicableAmount::excludingVat(Money::EUR(100)),
            Money::EUR(100),
        );

        $this->assertEquals(Money::EUR(100), $price->getExcludingVat());
        $this->assertEquals(Money::EUR(100), $price->getAuthoritativeAmount());
        $this->assertSame(TaxMode::Exclusive, $price->getTaxMode());
    }

    public function test_it_creates_an_including_vat_service_price_from_a_vat_applicable_amount(): void
    {
        $price = DefaultServicePrice::fromVatApplicableAmount(
            VatApplicableAmount::includingVat(Money::EUR(121)),
            Money::EUR(100),
        );

        $this->assertEquals(Money::EUR(100), $price->getExcludingVat());
        $this->assertEquals(Money::EUR(121), $price->getAuthoritativeAmount());
        $this->assertSame(TaxMode::Inclusive, $price->getTaxMode());
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

    public function test_partial_including_vat_discount_preserves_gross_authority(): void
    {
        $price = DefaultServicePrice::fromIncludingVat(Money::EUR(700), Money::EUR(579));
        $discount = DefaultDiscountPrice::fromIncludingVat(Money::EUR(121), Money::EUR(100));

        $result = $price->applyDiscount($discount);

        $this->assertEquals(Money::EUR(479), $result->getExcludingVat());
        $this->assertEquals(Money::EUR(579), $result->getAuthoritativeAmount());
        $this->assertSame(TaxMode::Inclusive, $result->getTaxMode());
    }

    public function test_excluding_vat_discount_changes_an_inclusive_service_to_net_authority(): void
    {
        $price = DefaultServicePrice::fromIncludingVat(Money::EUR(700), Money::EUR(579));

        $result = $price->applyDiscount(DefaultDiscountPrice::fromExcludingVat(Money::EUR(100)));

        $this->assertEquals(Money::EUR(479), $result->getExcludingVat());
        $this->assertEquals(Money::EUR(479), $result->getAuthoritativeAmount());
        $this->assertSame(TaxMode::Exclusive, $result->getTaxMode());
    }

    public function test_it_rejects_an_inclusive_discount_that_only_makes_gross_negative(): void
    {
        $this->expectException(PriceCannotBeNegative::class);

        DefaultServicePrice::fromIncludingVat(Money::EUR(100), Money::EUR(200))
            ->applyDiscount(DefaultDiscountPrice::fromIncludingVat(Money::EUR(150), Money::EUR(50)));
    }
}
