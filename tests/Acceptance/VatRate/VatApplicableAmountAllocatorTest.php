<?php

declare(strict_types=1);

namespace Tests\Acceptance\VatRate;

use Money\Money;
use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\VatRate\Allocator\ProRateAllocator;
use Thinktomorrow\Trader\Application\VatRate\Allocator\VatApplicableAmountAllocator;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemPrice;
use Thinktomorrow\Trader\Domain\Common\Price\VatApplicableAmount;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

final class VatApplicableAmountAllocatorTest extends TestCase
{
    private VatApplicableAmountAllocator $allocator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->allocator = new VatApplicableAmountAllocator(new ProRateAllocator);
    }

    public function test_excluding_vat_amount_remains_authoritative(): void
    {
        $result = $this->allocator->allocate(
            $this->itemTotals(['21' => 10000]),
            VatApplicableAmount::excludingVat(Money::EUR(700)),
        );

        $this->assertEquals(Money::EUR(700), $result->getTotalExcludingVat());
        $this->assertEquals(Money::EUR(147), $result->getTotalVat());
        $this->assertEquals(Money::EUR(847), $result->getTotalIncludingVat());
    }

    public function test_including_vat_amount_remains_authoritative_on_rounding_edge(): void
    {
        $result = $this->allocator->allocate(
            $this->itemTotals(['21' => 10000]),
            VatApplicableAmount::includingVat(Money::EUR(700)),
        );

        $this->assertEquals(Money::EUR(579), $result->getTotalExcludingVat());
        $this->assertEquals(Money::EUR(121), $result->getTotalVat());
        $this->assertEquals(Money::EUR(700), $result->getTotalIncludingVat());
        $this->assertEquals(Money::EUR(121), $result->findByRate('21')->getVatAmount());
    }

    public function test_including_vat_amount_uses_net_product_distribution_for_mixed_rates(): void
    {
        $result = $this->allocator->allocate(
            $this->itemTotals(['21' => 10000, '6' => 10000]),
            VatApplicableAmount::includingVat(Money::EUR(1000)),
        );

        $this->assertEquals(Money::EUR(881), $result->getTotalExcludingVat());
        $this->assertEquals(Money::EUR(1000), $result->getTotalIncludingVat());
        $this->assertEquals(Money::EUR(441), $result->findByRate('21')->getTaxableBase());
        $this->assertEquals(Money::EUR(440), $result->findByRate('6')->getTaxableBase());
    }

    public function test_result_is_independent_of_vat_group_order(): void
    {
        $first = $this->allocator->allocate(
            $this->itemTotals(['21' => 9999, '12' => 5000, '6' => 1234]),
            VatApplicableAmount::includingVat(Money::EUR(999)),
        );
        $second = $this->allocator->allocate(
            $this->itemTotals(['6' => 1234, '21' => 9999, '12' => 5000]),
            VatApplicableAmount::includingVat(Money::EUR(999)),
        );

        foreach (['21', '12', '6'] as $rate) {
            $this->assertEquals($first->findByRate($rate)->getTaxableBase(), $second->findByRate($rate)->getTaxableBase());
            $this->assertEquals($first->findByRate($rate)->getVatAmount(), $second->findByRate($rate)->getVatAmount());
        }

        $this->assertEquals(Money::EUR(999), $second->getTotalIncludingVat());
    }

    public function test_free_item_preserves_its_known_vat_rate(): void
    {
        $result = $this->allocator->allocate(
            $this->itemTotals(['21' => 0]),
            VatApplicableAmount::includingVat(Money::EUR(700)),
        );

        $this->assertEquals(Money::EUR(579), $result->getTotalExcludingVat());
        $this->assertEquals(Money::EUR(121), $result->getTotalVat());
        $this->assertEquals(Money::EUR(700), $result->getTotalIncludingVat());
    }

    public function test_empty_order_treats_amount_as_zero_percent(): void
    {
        $result = $this->allocator->allocate([], VatApplicableAmount::includingVat(Money::EUR(700)));

        $this->assertEquals(Money::EUR(700), $result->getTotalExcludingVat());
        $this->assertEquals(Money::EUR(0), $result->getTotalVat());
        $this->assertEquals(Money::EUR(700), $result->getTotalIncludingVat());
    }

    public function test_mixed_free_items_use_the_highest_known_vat_rate(): void
    {
        $result = $this->allocator->allocate(
            $this->itemTotals(['6' => 0, '21' => 0]),
            VatApplicableAmount::includingVat(Money::EUR(121)),
        );

        $this->assertEquals(Money::EUR(100), $result->getTotalExcludingVat());
        $this->assertEquals(Money::EUR(21), $result->getTotalVat());
        $this->assertEquals(Money::EUR(100), $result->findByRate('21')->getTaxableBase());
        $this->assertEquals(Money::EUR(0), $result->findByRate('6')->getTaxableBase());
    }

    public function test_large_amount_uses_exact_rational_resolution(): void
    {
        $result = $this->allocator->allocate(
            $this->itemTotals(['21' => 9999, '12' => 5000, '6' => 1234]),
            VatApplicableAmount::includingVat(Money::EUR('9007199254740993')),
        );

        $this->assertEquals(Money::EUR('9007199254740993'), $result->getTotalIncludingVat());
        $this->assertEquals(
            $result->getTotalIncludingVat(),
            $result->getTotalExcludingVat()->add($result->getTotalVat()),
        );
    }

    public function test_decimal_vat_rates_are_allocated_exactly(): void
    {
        $result = $this->allocator->allocate(
            $this->itemTotals(['12.25' => 5000, '6.5' => 10000, '1.234567' => 2500]),
            VatApplicableAmount::includingVat(Money::EUR(1000)),
        );

        $this->assertEquals(Money::EUR(1000), $result->getTotalIncludingVat());
        $this->assertNotNull($result->findByRate('12.25'));
        $this->assertNotNull($result->findByRate('6.5'));
        $this->assertNotNull($result->findByRate('1.234567'));
    }

    public function test_positive_reconciliation_uses_residual_and_rate_as_stable_tie_breaker(): void
    {
        $result = $this->allocator->allocate(
            $this->itemTotals(['21' => 10000, '6' => 10000]),
            VatApplicableAmount::includingVat(Money::EUR(999)),
        );

        $this->assertEquals(Money::EUR(999), $result->getTotalIncludingVat());
        $this->assertEquals(Money::EUR(93), $result->findByRate('21')->getVatAmount());
        $this->assertEquals(Money::EUR(26), $result->findByRate('6')->getVatAmount());
    }

    public function test_negative_reconciliation_reverses_the_largest_rounding_overshoot(): void
    {
        $result = $this->allocator->allocateResolved(
            $this->itemTotals(['21' => 10000, '6' => 10000]),
            VatApplicableAmount::includingVat(Money::EUR(999)),
            Money::EUR(881),
        );

        $this->assertEquals(Money::EUR(999), $result->getTotalIncludingVat());
        $this->assertEquals(Money::EUR(92), $result->findByRate('21')->getVatAmount());
        $this->assertEquals(Money::EUR(26), $result->findByRate('6')->getVatAmount());
    }

    public function test_allocate_resolved_rejects_an_unreconcilable_net_amount(): void
    {
        $this->expectException(\LogicException::class);

        $this->allocator->allocateResolved(
            $this->itemTotals(['21' => 10000, '6' => 10000]),
            VatApplicableAmount::includingVat(Money::EUR(1000)),
            Money::EUR(500),
        );
    }

    /** @param array<string, int> $amounts */
    private function itemTotals(array $amounts): array
    {
        $result = [];

        foreach ($amounts as $rate => $amount) {
            $result[$rate] = DefaultItemPrice::fromExcludingVat(
                Money::EUR($amount),
                VatPercentage::fromString((string) $rate),
            );
        }

        return $result;
    }
}
