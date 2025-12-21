<?php

namespace Tests\Unit\Common\Vat;

use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Vat\ProRateAllocator;

final class ProRateAllocatorTest extends TestCase
{
    private ProRateAllocator $allocator;

    protected function setUp(): void
    {
        $this->allocator = new ProRateAllocator();
    }

    public function test_it_allocates_pro_rata_and_preserves_total(): void
    {
        $items = [
            '21' => Money::EUR(10000),
            '6' => Money::EUR(5000),
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

    public function test_it_distributes_remainder_in_input_order(): void
    {
        $items = [
            '21' => Money::EUR(1),
            '6' => Money::EUR(1),
        ];

        $toAllocate = Money::EUR(1);

        $result = $this->allocator->allocate($items, $toAllocate);

        $this->assertEquals(Money::EUR(1), $result['21']);
        $this->assertEquals(Money::EUR(0), $result['6']);
    }

    public function test_zero_allocation_returns_zero_for_all_groups(): void
    {
        $items = [
            '21' => Money::EUR(1000),
            '6' => Money::EUR(1000),
        ];

        $result = $this->allocator->allocate($items, Money::EUR(0));

        $this->assertEquals(Money::EUR(0), $result['21']);
        $this->assertEquals(Money::EUR(0), $result['6']);
    }

    public function test_allocation_when_item_totals_are_zero(): void
    {
        $items = [
            '21' => Money::EUR(0),
            '6' => Money::EUR(0),
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
            '21' => Money::EUR(10000),
            '6' => Money::EUR(5000),
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
        int   $toAllocate,
        array $expected
    ): void {
        $moneyItems = array_map(fn ($v) => Money::EUR($v), $items);
        $total = Money::EUR($toAllocate);

        $result = $this->allocator->allocate($moneyItems, $total);

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
                ['21' => 2, '6' => 2, '12' => 1],
            ],

            // --- Small remainder spill negative ---
            'Small remainder spill negative' => [
                ['21' => 1, '6' => 1, '12' => 1],
                -5,
                ['21' => -2, '6' => -2, '12' => -1],
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

            // --- Order stability check ---
            'order stability matters' => [
                ['6' => 1, '21' => 1],
                1,
                ['6' => 1, '21' => 0],
            ],
        ];
    }

    public function test_remainder_never_exceeds_number_of_groups_positive(): void
    {
        $items = [
            '21' => Money::EUR(1),
            '6' => Money::EUR(1),
            '12' => Money::EUR(1),
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
            '21' => Money::EUR(1),
            '6' => Money::EUR(1),
            '12' => Money::EUR(1),
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
            '21' => Money::EUR(999999),
            '6' => Money::EUR(1),
            '12' => Money::EUR(1),
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
            '21' => Money::EUR(1),
            '6' => Money::EUR(1),
            '12' => Money::EUR(1),
            '0' => Money::EUR(1),
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
            '21' => Money::EUR(10),
            '6' => Money::EUR(10),
            '12' => Money::EUR(10),
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
                '21' => Money::EUR(random_int(0, 10000)),
                '6' => Money::EUR(random_int(0, 10000)),
                '12' => Money::EUR(random_int(0, 10000)),
                '0' => Money::EUR(random_int(0, 10000)),
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
