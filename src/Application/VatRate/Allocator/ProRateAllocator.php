<?php

namespace Thinktomorrow\Trader\Application\VatRate\Allocator;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\ItemPrice;

class ProRateAllocator
{
    /**
     * Allocate a monetary amount proportionally across VAT groups using minor-unit integer math.
     *
     * Truncated shares are reconciled using the largest-remainder method. Equal remainders are
     * resolved by highest VAT rate, making the result independent of the input array order.
     * The returned allocations always add up exactly to the requested amount.
     *
     * @param  array<string, ItemPrice>  $itemTotalsPerRate
     * @return array<string, Money>
     */
    public function allocate(array $itemTotalsPerRate, Money $totalToAllocate): array
    {
        $this->assertItemTotalsAreInstanceOfItemPrice($itemTotalsPerRate);
        $this->assertItemTotalsAreKeyedWithRates($itemTotalsPerRate);

        // Preserve all known VAT groups even when there is no amount to allocate.
        if ($totalToAllocate->isZero()) {
            return $this->mapToZero($totalToAllocate, $itemTotalsPerRate);
        }

        if ($itemTotalsPerRate === []) {
            throw new \InvalidArgumentException('Cannot allocate a non-zero amount without VAT groups.');
        }

        $sum = $this->sumItemsExcl($itemTotalsPerRate);

        // Without a proportional basis, assign the full amount to the highest VAT rate as a stable fallback.
        if ($sum->isZero()) {
            $result = $this->mapToZero($totalToAllocate, $itemTotalsPerRate);

            if ($result === []) {
                return [];
            }

            $rates = array_keys($itemTotalsPerRate);
            usort($rates, fn (string|int $left, string|int $right): int => $this->compareVatRatesDescending((string) $left, (string) $right));

            $firstKey = $rates[0];
            $result[$firstKey] = $totalToAllocate;

            return $result;
        }

        $currency = $totalToAllocate->getCurrency();
        $totalMinor = $totalToAllocate->getAmount();
        $sumMinor = $sum->getAmount();
        $alloc = [];
        $allocatedSum = '0';
        $fractionalRemainders = [];

        // Keep each exact share as an integer quotient plus its residual numerator. The common
        // denominator means residuals can be compared directly without floats or precision loss.
        foreach ($itemTotalsPerRate as $rate => $itemPricePerRate) {
            $product = bcmul($totalMinor, $itemPricePerRate->getExcludingVat()->getAmount(), 0);
            $minor = bcdiv($product, $sumMinor, 0);
            $fractionalRemainder = bcsub($product, bcmul($minor, $sumMinor, 0), 0);

            // Normalize residuals to a positive-denominator orientation so their sort order remains valid.
            if (bccomp($sumMinor, '0', 0) < 0) {
                $fractionalRemainder = bcsub('0', $fractionalRemainder, 0);
            }

            $alloc[$rate] = new Money($minor, $currency);
            $allocatedSum = bcadd($allocatedSum, $minor, 0);
            $fractionalRemainders[] = [
                'rate' => $rate,
                'vat_rate' => $itemPricePerRate->getVatPercentage()->get(),
                'remainder' => $fractionalRemainder,
            ];
        }

        $remainder = bcsub($totalMinor, $allocatedSum, 0);

        if (bccomp($remainder, '0', 0) === 0) {
            return $alloc;
        }

        $adjustment = bccomp($remainder, '0', 0) > 0 ? '1' : '-1';

        // Positive totals receive cents at the largest residuals; negative totals remove cents
        // at the mirrored residuals. VAT rate is the deterministic tie-breaker in both directions.
        usort($fractionalRemainders, function (array $left, array $right) use ($adjustment): int {
            $remainderComparison = bccomp($right['remainder'], $left['remainder'], 0);

            if ($adjustment === '-1') {
                $remainderComparison *= -1;
            }

            return $remainderComparison !== 0
                ? $remainderComparison
                : $this->compareVatRatesDescending($left['vat_rate'], $right['vat_rate']);
        });

        foreach ($fractionalRemainders as $fractionalRemainder) {
            if (bccomp($remainder, '0', 0) === 0) {
                break;
            }

            $rate = $fractionalRemainder['rate'];
            $alloc[$rate] = $alloc[$rate]->add(new Money($adjustment, $currency));
            $remainder = bcsub($remainder, $adjustment, 0);
        }

        // Truncating one share per group can leave at most one minor unit per group to reconcile.
        if (bccomp($remainder, '0', 0) !== 0) {
            throw new \LogicException('ProRateAllocator remainder leak detected: '.$remainder);
        }

        return $alloc;
    }

    /** @return array<string, Money> */
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

    private function compareVatRatesDescending(string $left, string $right): int
    {
        $leftDecimals = str_contains($left, '.') ? strlen(substr(strrchr($left, '.'), 1)) : 0;
        $rightDecimals = str_contains($right, '.') ? strlen(substr(strrchr($right, '.'), 1)) : 0;

        return bccomp($right, $left, max($leftDecimals, $rightDecimals));
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
                throw new \InvalidArgumentException('itemTotalsPerRate must be an array of ItemPrice instances. Got '.get_debug_type($itemTotal));
            }
        }
    }
}
