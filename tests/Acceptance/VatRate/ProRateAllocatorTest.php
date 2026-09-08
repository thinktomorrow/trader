<?php

namespace Acceptance\VatRate;

use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Application\VatRate\Allocator\ProRateAllocator;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemPrice;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

final class ProRateAllocatorTest extends TestCase
{
    private ProRateAllocator $allocator;

    protected function setUp(): void
    {
        $this->allocator = new ProRateAllocator;
    }

    public function test_it_allocates_pro_rata_and_preserves_total(): void
    {
        $items = [
            '21' => DefaultItemPrice::fromExcludingVat(Money::EUR(10000), VatPercentage::fromString('21')),
            '6' => DefaultItemPrice::fromExcludingVat(Money::EUR(5000), VatPercentage::fromString('6')),
        ];

        $toAllocate = Money::EUR(1000);

        $result = $this->allocator->allocate($items, $toAllocate);

        $this->assertEquals(Money::EUR(667), $result['21']);
        $this->assertEquals(Money::EUR(333), $result['6']);

        $this->assertEquals(
            $toAllocate,
            $result['21']->add($result['6'])
        );
    }

    public function test_it_protects_against_mismatch_of_vat_percentage(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $items = [
            '21' => DefaultItemPrice::fromExcludingVat(Money::EUR(10), VatPercentage::fromString('6')),
        ];

        $toAllocate = Money::EUR(100);

        $this->allocator->allocate($items, $toAllocate);
    }

    public function test_it_uses_vat_rate_as_stable_tie_breaker(): void
    {
        $items = [
            '21' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('21')),
            '6' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('6')),
        ];

        $toAllocate = Money::EUR(1);

        $result = $this->allocator->allocate($items, $toAllocate);

        $this->assertEquals(Money::EUR(1), $result['21']);
        $this->assertEquals(Money::EUR(0), $result['6']);
    }

    public function test_largest_fractional_remainder_is_independent_of_input_order(): void
    {
        $firstOrder = [
            '21' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('21')),
            '12' => DefaultItemPrice::fromExcludingVat(Money::EUR(2), VatPercentage::fromString('12')),
            '6' => DefaultItemPrice::fromExcludingVat(Money::EUR(3), VatPercentage::fromString('6')),
        ];
        $secondOrder = [
            '6' => $firstOrder['6'],
            '21' => $firstOrder['21'],
            '12' => $firstOrder['12'],
        ];

        $firstResult = $this->allocator->allocate($firstOrder, Money::EUR(2));
        $secondResult = $this->allocator->allocate($secondOrder, Money::EUR(2));

        foreach (['21', '12', '6'] as $rate) {
            $this->assertEquals($firstResult[$rate], $secondResult[$rate]);
        }

        $this->assertEquals(Money::EUR(0), $firstResult['21']);
        $this->assertEquals(Money::EUR(1), $firstResult['12']);
        $this->assertEquals(Money::EUR(1), $firstResult['6']);
    }

    #[DataProvider('tiedRemainderCases')]
    public function test_tied_remainders_are_stable_for_positive_and_negative_totals(int $total, array $expected): void
    {
        $items = [
            '6' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('6')),
            '21' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('21')),
            '12' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('12')),
        ];

        $result = $this->allocator->allocate($items, Money::EUR($total));

        foreach ($expected as $rate => $amount) {
            $this->assertEquals(Money::EUR($amount), $result[$rate]);
        }
    }

    public static function tiedRemainderCases(): array
    {
        return [
            'positive' => [2, ['21' => 1, '12' => 1, '6' => 0]],
            'negative' => [-2, ['21' => -1, '12' => -1, '6' => 0]],
        ];
    }

    public function test_it_allocates_exactly_beyond_float_integer_precision(): void
    {
        $items = [
            '21' => DefaultItemPrice::fromExcludingVat(Money::EUR(2), VatPercentage::fromString('21')),
            '6' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('6')),
        ];

        $result = $this->allocator->allocate($items, Money::EUR('9007199254740993'));

        $this->assertEquals(Money::EUR('6004799503160662'), $result['21']);
        $this->assertEquals(Money::EUR('3002399751580331'), $result['6']);
        $this->assertEquals(Money::EUR('9007199254740993'), $result['21']->add($result['6']));
    }

    public function test_zero_allocation_returns_zero_for_all_groups(): void
    {
        $items = [
            '21' => DefaultItemPrice::fromExcludingVat(Money::EUR(10000), VatPercentage::fromString('21')),
            '6' => DefaultItemPrice::fromExcludingVat(Money::EUR(10000), VatPercentage::fromString('6')),
        ];

        $result = $this->allocator->allocate($items, Money::EUR(0));

        $this->assertEquals(Money::EUR(0), $result['21']);
        $this->assertEquals(Money::EUR(0), $result['6']);
    }

    public function test_zero_bases_use_a_stable_vat_rate_independent_of_input_order(): void
    {
        $first = [
            '6' => DefaultItemPrice::fromExcludingVat(Money::EUR(0), VatPercentage::fromString('6')),
            '21' => DefaultItemPrice::fromExcludingVat(Money::EUR(0), VatPercentage::fromString('21')),
        ];
        $second = array_reverse($first, true);

        $firstResult = $this->allocator->allocate($first, Money::EUR(10));
        $secondResult = $this->allocator->allocate($second, Money::EUR(10));

        foreach (['21', '6'] as $rate) {
            $this->assertEquals($firstResult[$rate], $secondResult[$rate]);
        }

        $this->assertEquals(Money::EUR(10), $firstResult['21']);
    }

    public function test_non_zero_amount_requires_vat_groups(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->allocator->allocate([], Money::EUR(1));
    }

    public function test_it_rejects_non_item_price_values_with_a_domain_error(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Got string');

        $this->allocator->allocate(['21' => 'invalid'], Money::EUR(100));
    }

    public function test_allocation_when_item_totals_are_zero(): void
    {
        $items = [
            '21' => DefaultItemPrice::fromExcludingVat(Money::EUR(0), VatPercentage::fromString('21')),
            '6' => DefaultItemPrice::fromExcludingVat(Money::EUR(0), VatPercentage::fromString('6')),
        ];

        $toAllocate = Money::EUR(500);

        $result = $this->allocator->allocate($items, $toAllocate);

        // Entire amount goes to first VAT rate
        $this->assertEquals(Money::EUR(500), $result['21']);
        $this->assertEquals(Money::EUR(0), $result['6']);
    }

    public function test_negative_allocation_is_supported(): void
    {
        $items = [
            '21' => DefaultItemPrice::fromExcludingVat(Money::EUR(10000), VatPercentage::fromString('21')),
            '6' => DefaultItemPrice::fromExcludingVat(Money::EUR(5000), VatPercentage::fromString('6')),
        ];

        $toAllocate = Money::EUR(-1000);

        $result = $this->allocator->allocate($items, $toAllocate);

        $this->assertEquals(Money::EUR(-667), $result['21']);
        $this->assertEquals(Money::EUR(-333), $result['6']);

        $this->assertEquals(
            $toAllocate,
            $result['21']->add($result['6'])
        );
    }

    #[DataProvider('remainderCases')]
    public function test_remainder_distribution_edge_cases(
        array $items,
        int $toAllocate,
        array $expected
    ): void {
        // Map items to DefaultItemPrices
        $itemPrices = [];

        foreach ($items as $rate => $amount) {
            $itemPrices[$rate] = DefaultItemPrice::fromExcludingVat(Money::EUR($amount), VatPercentage::fromString($rate));
        }

        $total = Money::EUR($toAllocate);

        $result = $this->allocator->allocate($itemPrices, $total);

        foreach ($expected as $rate => $amount) {
            $this->assertEquals(
                Money::EUR($amount),
                $result[$rate],
                "Mismatch for VAT rate {$rate}"
            );
        }

        // invariant: sum must always match
        $sum = Money::EUR(0);
        foreach ($result as $money) {
            $sum = $sum->add($money);
        }

        $this->assertEquals($total, $sum);
    }

    public static function remainderCases(): array
    {
        return [

            // --- Simple positive remainder ---
            '2 groups, +1 remainder' => [
                ['21' => 1, '6' => 1],
                1,
                ['21' => 1, '6' => 0],
            ],

            // --- Simple negative remainder ---
            '2 groups, -1 remainder' => [
                ['21' => 1, '6' => 1],
                -1,
                ['21' => -1, '6' => 0],
            ],

            // --- Uneven ratios, positive ---
            '2/3 vs 1/3 positive' => [
                ['21' => 10000, '6' => 5000],
                1000,
                ['21' => 667, '6' => 333],
            ],

            // --- Uneven ratios, negative ---
            '2/3 vs 1/3 negative' => [
                ['21' => 10000, '6' => 5000],
                -1000,
                ['21' => -667, '6' => -333],
            ],

            // --- Very small total, many groups ---
            'many groups, tiny total' => [
                ['21' => 100, '6' => 100, '12' => 100],
                1,
                ['21' => 1, '6' => 0, '12' => 0],
            ],

            // --- Very small negative total ---
            'many groups, tiny negative total' => [
                ['21' => 100, '6' => 100, '12' => 100],
                -1,
                ['21' => -1, '6' => 0, '12' => 0],
            ],

            // --- Small remainder spill ---
            'Small remainder spill positive' => [
                ['21' => 1, '6' => 1, '12' => 1],
                5,
                ['21' => 2, '6' => 1, '12' => 2],
            ],

            // --- Small remainder spill negative ---
            'Small remainder spill negative' => [
                ['21' => 1, '6' => 1, '12' => 1],
                -5,
                ['21' => -2, '6' => -1, '12' => -2],
            ],

            // --- Large remainder spill ---
            'Large remainder spill positive' => [
                ['21' => 1, '6' => 1, '12' => 1],
                100,
                ['21' => 34, '6' => 33, '12' => 33],
            ],

            // --- Large remainder spill negative ---
            'Large remainder spill negative' => [
                ['21' => 1, '6' => 1, '12' => 1],
                -100,
                ['21' => -34, '6' => -33, '12' => -33],
            ],

            // --- One dominant group ---
            'one dominant group' => [
                ['21' => 9999, '6' => 1],
                100,
                ['21' => 100, '6' => 0],
            ],

            // --- Zero groups except one ---
            'mixed zero groups' => [
                ['21' => 0, '6' => 100],
                3,
                ['21' => 0, '6' => 3],
            ],

            // --- All zeros except remainder ---
            'all zero items' => [
                ['21' => 0, '6' => 0],
                10,
                ['21' => 10, '6' => 0],
            ],

            // --- Tie-breaker is independent of input order ---
            'VAT rate tie-breaker' => [
                ['6' => 1, '21' => 1],
                1,
                ['6' => 0, '21' => 1],
            ],
        ];
    }

    public function test_remainder_never_exceeds_number_of_groups_positive(): void
    {
        $items = [
            '21' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('21')),
            '6' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('6')),
            '12' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('12')),
        ];

        $toAllocate = Money::EUR(10);

        $result = $this->allocator->allocate($items, $toAllocate);

        $sum = Money::EUR(0);
        foreach ($result as $money) {
            $sum = $sum->add($money);
        }

        $this->assertEquals($toAllocate, $sum);
    }

    public function test_remainder_never_exceeds_number_of_groups_negative(): void
    {
        $items = [
            '21' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('21')),
            '6' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('6')),
            '12' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('12')),
        ];

        $toAllocate = Money::EUR(-10);

        $result = $this->allocator->allocate($items, $toAllocate);

        $sum = Money::EUR(0);
        foreach ($result as $money) {
            $sum = $sum->add($money);
        }

        $this->assertEquals($toAllocate, $sum);
    }

    public function test_extreme_ratio_does_not_break_remainder_distribution(): void
    {
        $items = [
            '21' => DefaultItemPrice::fromExcludingVat(Money::EUR(999999), VatPercentage::fromString('21')),
            '6' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('6')),
            '12' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('12')),
        ];

        $toAllocate = Money::EUR(5);

        $result = $this->allocator->allocate($items, $toAllocate);

        $this->assertEquals(Money::EUR(5), $result['21']);
        $this->assertEquals(Money::EUR(0), $result['6']);
        $this->assertEquals(Money::EUR(0), $result['12']);
    }

    public function test_all_zero_initial_allocations_large_remainder(): void
    {
        $items = [
            '21' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('21')),
            '6' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('6')),
            '12' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('12')),
            '0' => DefaultItemPrice::fromExcludingVat(Money::EUR(1), VatPercentage::fromString('0')),
        ];

        $toAllocate = Money::EUR(3);

        $result = $this->allocator->allocate($items, $toAllocate);

        $this->assertEquals(Money::EUR(1), $result['21']);
        $this->assertEquals(Money::EUR(1), $result['6']);
        $this->assertEquals(Money::EUR(1), $result['12']);
        $this->assertEquals(Money::EUR(0), $result['0']);
    }

    public function test_remainder_equal_to_key_count(): void
    {
        $items = [
            '21' => DefaultItemPrice::fromExcludingVat(Money::EUR(10), VatPercentage::fromString('21')),
            '6' => DefaultItemPrice::fromExcludingVat(Money::EUR(10), VatPercentage::fromString('6')),
            '12' => DefaultItemPrice::fromExcludingVat(Money::EUR(10), VatPercentage::fromString('12')),
        ];

        $toAllocate = Money::EUR(3);

        $result = $this->allocator->allocate($items, $toAllocate);

        $this->assertEquals(Money::EUR(1), $result['21']);
        $this->assertEquals(Money::EUR(1), $result['6']);
        $this->assertEquals(Money::EUR(1), $result['12']);
    }

    public function test_remainder_is_always_smaller_than_group_count(): void
    {
        for ($i = 0; $i < 200; $i++) {
            $items = [
                '21' => DefaultItemPrice::fromExcludingVat(Money::EUR(random_int(0, 10000)), VatPercentage::fromString('21')),
                '6' => DefaultItemPrice::fromExcludingVat(Money::EUR(random_int(0, 10000)), VatPercentage::fromString('6')),
                '12' => DefaultItemPrice::fromExcludingVat(Money::EUR(random_int(0, 10000)), VatPercentage::fromString('12')),
                '0' => DefaultItemPrice::fromExcludingVat(Money::EUR(random_int(0, 10000)), VatPercentage::fromString('0')),
            ];

            $toAllocate = Money::EUR(random_int(-10000, 10000));

            $result = $this->allocator->allocate($items, $toAllocate);

            $sum = Money::EUR(0);
            foreach ($result as $money) {
                $sum = $sum->add($money);
            }

            $this->assertEquals($toAllocate, $sum);
        }
    }
}
