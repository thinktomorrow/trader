<?php

namespace Thinktomorrow\Trader\Application\VatRate\Allocator;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\ItemPrice;

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
     * @param  array<string, ItemPrice>  $itemTotalsPerRate  bv. ['21' => ItemPrice(1210), '9' => ItemPrice(1090)]
     * @param  Money  $totalToAllocate  bv. Money(1000)
     * @return array<string, Money>
     */
    public function allocate(array $itemTotalsPerRate, Money $totalToAllocate): array
    {
        $this->assertItemTotalsAreInstanceOfItemPrice($itemTotalsPerRate);
        $this->assertItemTotalsAreKeyedWithRates($itemTotalsPerRate);

        /**
         * Edge cases:
         *
         * - nothing to allocate (no shipping, payment costs and no discounts).
         */
        if ($totalToAllocate->isZero()) {
            return $this->mapToZero($totalToAllocate, $itemTotalsPerRate);
        }

        $sum = $this->sumItemsExcl($itemTotalsPerRate);

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
            $result = $this->mapToZero($totalToAllocate, $itemTotalsPerRate);

            $firstKey = array_key_first($itemTotalsPerRate);
            $result[$firstKey] = $totalToAllocate;

            return $result;
        }

        $currency = $totalToAllocate->getCurrency();
        $totalMinor = (int) $totalToAllocate->getAmount(); // centen
        $alloc = [];
        $allocatedSum = new Money('0', $currency);

        // 1) voorlopige allocaties (floor per ratio)
        foreach ($itemTotalsPerRate as $rate => $itemPricePerRate) {
            $ratio = bcdiv(
                (string) $itemPricePerRate->getExcludingVat()->getAmount(),
                (string) $sum->getAmount(),
                12 // genoeg precisie voor ratio
            );

            $minor = $this->truncateTowardZero($totalMinor * (float) $ratio);

            $alloc[$rate] = new Money((string) $minor, $currency);
            $allocatedSum = $allocatedSum->add($alloc[$rate]);
        }

        // 2) remainder in minor units (mag niet blijven liggen)
        $remainder = $totalMinor - (int) $allocatedSum->getAmount();

        if ($remainder === 0) {
            return $alloc; // perfect gesplitst
        }

        // 3) remainder stabiel verdelen over de keys in volgorde
        foreach ($alloc as $rate => $money) {
            if ($remainder === 0) {
                break;
            }

            $adjustment = $remainder > 0 ? 1 : -1;

            $alloc[$rate] = $money->add(new Money((string) $adjustment, $currency));

            $remainder -= $adjustment;
        }

        if ($remainder !== 0) {
            throw new \LogicException('ProRateAllocator remainder leak detected: '.$remainder);
        }

        return $alloc;
    }

    /**
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

    private function sumItemsExcl(array $itemPricesPerRate): Money
    {
        $sum = Cash::zero();

        foreach ($itemPricesPerRate as $itemPrice) {
            $sum = $sum->add($itemPrice->getExcludingVat());
        }

        return $sum;
    }

    /**
     * Allows for negative values to be truncated toward zero.
     */
    private function truncateTowardZero(float $value): int
    {
        return $value >= 0 ? (int) floor($value) : (int) ceil($value);
    }

    private function assertItemTotalsAreKeyedWithRates(array $itemTotalsPerRate): void
    {
        foreach ($itemTotalsPerRate as $rate => $itemTotal) {
            if (! is_string($rate) && ! is_int($rate)) {
                throw new \InvalidArgumentException('itemTotalsPerRate must be an array keyed by VAT rates. Got key '.gettype($rate));
            }

            if ($itemTotal->getVatPercentage()->get() !== (string) $rate) {
                throw new \InvalidArgumentException('itemTotalsPerRate key must match ItemPrice VAT rate. Got key '.$rate.' but ItemPrice has VAT rate '.$itemTotal->getVatPercentage()->get());
            }
        }
    }

    private function assertItemTotalsAreInstanceOfItemPrice(array $itemTotalsPerRate): void
    {
        foreach ($itemTotalsPerRate as $itemTotal) {
            if (! $itemTotal instanceof ItemPrice) {
                throw new \InvalidArgumentException('itemTotalsPerRate must be an array of ItemPrice instances. Got '.$itemTotal::class);
            }
        }
    }
}
