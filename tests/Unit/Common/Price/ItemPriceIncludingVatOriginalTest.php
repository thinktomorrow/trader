<?php

namespace Tests\Unit\Common\Price;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemPrice;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

final class ItemPriceIncludingVatOriginalTest extends TestCase
{
    public function test_excluding_vat_is_always_canonical_even_when_including_is_preserved(): void
    {
        $item = DefaultItemPrice::fromMoney(
            Money::EUR(1000), // incl
            VatPercentage::fromString('21'),
            true
        );

        $total = $item->multiply(3);

        // Excluding VAT must always equal incl - VAT
        $this->assertEquals(
            $total->getIncludingVat()->subtract($total->getVatTotal()),
            $total->getExcludingVat()
        );
    }

    public function test_multiply_without_original_including_vat_recalculates_including(): void
    {
        // €8.26 excl → €10.00 incl
        $item = DefaultItemPrice::fromMoney(
            Money::EUR(826),
            VatPercentage::fromString('21'),
            false
        );

        $total = $item->multiply(2);

        // incl is recalculated (not preserved)
        $this->assertEquals(Money::EUR(1999), $total->getIncludingVat());
        $this->assertEquals(Money::EUR(1652), $total->getExcludingVat());
        $this->assertEquals(Money::EUR(347), $total->getVatTotal());
    }

    public function test_multiply_preserves_original_including_vat_exactly(): void
    {
        // €10.00 incl. 21% VAT
        $item = DefaultItemPrice::fromMoney(
            Money::EUR(1000),
            VatPercentage::fromString('21'),
            true
        );

        $total = $item->multiply(2);

        // Including VAT is preserved exactly when originally provided
        $this->assertEquals(Money::EUR(2000), $total->getIncludingVat());

        // Excluding VAT remains the canonical calculation base
        $this->assertEquals(Money::EUR(1652), $total->getExcludingVat());

        // VAT absorbs any rounding differences 347 -> 348
        $this->assertEquals(Money::EUR(348), $total->getVatTotal());
    }

    public function test_multiply_preserves_original_including_vat_exactly_with_large_diffs(): void
    {
        // €10.00 incl. 21% VAT
        $item = DefaultItemPrice::fromMoney(
            Money::EUR(1000),
            VatPercentage::fromString('21'),
            true
        );

        $total = $item->multiply(20);

        // Including VAT is preserved exactly when originally provided
        $this->assertEquals(Money::EUR(20000), $total->getIncludingVat());

        // Excluding VAT remains the canonical calculation base
        $this->assertEquals(Money::EUR(16520), $total->getExcludingVat());

        // VAT absorbs any rounding differences 3470 -> 3480
        $this->assertEquals(Money::EUR(3480), $total->getVatTotal());
    }

    public function test_apply_discount_preserves_original_including_vat(): void
    {
        // €10.00 incl
        $item = DefaultItemPrice::fromMoney(
            Money::EUR(1000),
            VatPercentage::fromString('21'),
            true
        );

        // €1.00 excl discount
        $discount = DefaultDiscountPrice::fromExcludingVat(Money::EUR(100));

        $result = $item->applyDiscount($discount);

        // discount incl = round(1 × 1.21) = 1.21
        $this->assertEquals(Money::EUR(879), $result->getIncludingVat());
        $this->assertEquals(Money::EUR(726), $result->getExcludingVat());
        $this->assertEquals(Money::EUR(153), $result->getVatTotal());
    }

    public function test_multiply_then_discount_keeps_including_vat_consistent(): void
    {
        // €10.00 incl
        $item = DefaultItemPrice::fromMoney(
            Money::EUR(1000),
            VatPercentage::fromString('21'),
            true
        );

        // €0.50 excl discount
        $discount = DefaultDiscountPrice::fromExcludingVat(Money::EUR(50));

        $result = $item
            ->multiply(2)          // €20.00 incl
            ->applyDiscount($discount);

        // discount incl = round(0.50 × 1.21) = 0.61
        $this->assertEquals(Money::EUR(1939), $result->getIncludingVat());
        $this->assertEquals(Money::EUR(1602), $result->getExcludingVat());
        $this->assertEquals(Money::EUR(337), $result->getVatTotal());
    }

    public function test_original_including_vat_is_tracked(): void
    {
        $incl = DefaultItemPrice::fromMoney(
            Money::EUR(1000),
            VatPercentage::fromString('21'),
            true
        );

        $excl = DefaultItemPrice::fromMoney(
            Money::EUR(826),
            VatPercentage::fromString('21'),
            false
        );

        $this->assertTrue($incl->hasOriginalIncludingVat());
        $this->assertFalse($excl->hasOriginalIncludingVat());
    }

    public function test_original_including_vat_survives_multiple_operations(): void
    {
        $item = DefaultItemPrice::fromMoney(
            Money::EUR(999), // €9.99 incl
            VatPercentage::fromString('21'),
            true
        );

        $discount = DefaultDiscountPrice::fromExcludingVat(Money::EUR(1));

        $result = $item
            ->multiply(3)
            ->applyDiscount($discount)
            ->multiply(2);

        // original incl path must stay consistent
        $this->assertEquals(
            $result->getIncludingVat(),
            $result->getExcludingVat()->add($result->getVatTotal())
        );
    }
}
