<?php

namespace Thinktomorrow\Trader\Domain\Common\Vat;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;

class ProRateAllocator
{
    /**
     * Pro-rata allocation across VAT groups.
     *
     * Stable ordering:
     * - remainder-centen worden verdeeld in de volgorde van de input array.
     *
     * Guaranteert:
     * - som(allocaties) === $totalToAllocate
     * - geen verloren centen / remainders
     *
     * @param array<string, Money> $itemTotals bv. ['21' => Money(10000), '6' => Money(5000)]
     * @param Money $totalToAllocate bv. Money(1000)
     *
     * @return array<string, Money>
     */
    public function allocate(array $itemTotals, Money $totalToAllocate): array
    {
        /**
         * Edge cases:
         *
         * - nothing to allocate (no shipping, payment costs and no discounts).
         */
        if ($totalToAllocate->isZero()) {
            return $this->mapToZero($totalToAllocate, $itemTotals);
        }

        $sum = $this->sum($itemTotals);

        /**
         * Edge cases:
         *
         * - No / Free items, only shipping
         * - All item totals zero
         * - Massive order discount making subtotal zero
         * - Multi-VAT but all zero
         *
         * In these cases, we allocate the full amount to the first VAT rate.
         */
        if ($sum->isZero()) {
            $result = $this->mapToZero($totalToAllocate, $itemTotals);

            $firstKey = array_key_first($itemTotals);
            $result[$firstKey] = $totalToAllocate;

            return $result;
        }

        $currency = $totalToAllocate->getCurrency();
        $totalMinor = (int)$totalToAllocate->getAmount(); // centen
        $alloc = [];
        $allocatedSum = new Money('0', $currency);

        // 1) voorlopige allocaties (floor per ratio)
        foreach ($itemTotals as $rate => $value) {
            $ratio = bcdiv(
                (string)$value->getAmount(),
                (string)$sum->getAmount(),
                12 // genoeg precisie voor ratio
            );

            $minor = $this->truncateTowardZero($totalMinor * (float)$ratio);

            $alloc[$rate] = new Money((string)$minor, $currency);
            $allocatedSum = $allocatedSum->add($alloc[$rate]);
        }

        // 2) remainder in minor units (mag niet blijven liggen)
        $remainder = $totalMinor - (int)$allocatedSum->getAmount();

        if ($remainder === 0) {
            return $alloc; // perfect gesplitst
        }

        // 3) remainder stabiel verdelen over de keys in volgorde
        foreach ($alloc as $rate => $money) {
            if ($remainder === 0) {
                break;
            }

            $adjustment = $remainder > 0 ? 1 : -1;

            $alloc[$rate] = $money->add(new Money((string)$adjustment, $currency));

            $remainder -= $adjustment;
        }

        if ($remainder !== 0) {
            throw new \LogicException('ProRateAllocator remainder leak detected: ' . $remainder);
        }

        return $alloc;
    }

    /**
     * @param Money $totalToAllocate
     * @param array $itemTotals
     * @return Money[]
     */
    private function mapToZero(Money $totalToAllocate, array $itemTotals): array
    {
        $currency = $totalToAllocate->getCurrency();
        $result = array_map(
            fn () => new Money('0', $currency),
            $itemTotals
        );

        return $result;
    }

    private function sum(array $amounts): Money
    {
        $sum = Cash::zero();

        foreach ($amounts as $money) {
            $sum = $sum->add($money);
        }

        return $sum;
    }

    /**
     * Allows for negative values to be truncated toward zero.
     */
    private function truncateTowardZero(float $value): int
    {
        return $value >= 0 ? (int)floor($value) : (int)ceil($value);
    }
}
