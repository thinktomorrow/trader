<?php

namespace Thinktomorrow\Trader\Domain\Common\Vat;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Vat\Exceptions\InvalidVatAllocatedTotal;

/**
 * Result object of the VAT allocation process.
 *
 * Represents the fully resolved, tax-correct totals of an order or invoice:
 * - total excluding VAT
 * - total VAT
 * - total including VAT
 * - per-VAT-rate taxable bases and VAT amounts
 *
 * This object contains ONLY fully allocated and legally accurate values,
 * after all line items, service items, and discounts have been processed.
 *
 * Immutable value object.
 */
final class VatAllocatedTotalPrice
{
    /** @var VatAllocatedLine[] */
    private array $vatLines;

    private Money $totalExcluding;

    private Money $totalVat;

    private Money $totalIncluding;

    /**
     * @param  VatAllocatedLine[]  $vatLines  List of VAT lines, one per VAT percentage.
     * @param  Money  $totalExcluding  Total excluding VAT (= sum of all taxable bases)
     * @param  Money  $totalVat  Total VAT amount (= sum of all VAT amounts)
     * @param  Money  $totalIncluding  Total including VAT (= totalExcluding + totalVat)
     */
    public function __construct(array $vatLines, Money $totalExcluding, Money $totalVat, Money $totalIncluding)
    {
        $this->validateLines($vatLines);
        $this->validateTotals($vatLines, $totalExcluding, $totalVat, $totalIncluding);

        $this->vatLines = $vatLines;
        $this->totalExcluding = $totalExcluding;
        $this->totalVat = $totalVat;
        $this->totalIncluding = $totalIncluding;
    }

    /**
     * @return VatAllocatedLine[]
     */
    public function getVatLines(): array
    {
        return $this->vatLines;
    }

    /**
     * Total amount excluding VAT.
     */
    public function getTotalExcludingVat(): Money
    {
        return $this->totalExcluding;
    }

    /**
     * Total VAT amount aggregated across all VAT percentages.
     */
    public function getTotalVat(): Money
    {
        return $this->totalVat;
    }

    /**
     * Total including VAT across all VAT percentages.
     */
    public function getTotalIncludingVat(): Money
    {
        return $this->totalIncluding;
    }

    /**
     * Convenience access: return a vatLine for a specific vat rate.
     */
    public function findByRate(string $rate): ?VatAllocatedLine
    {
        foreach ($this->vatLines as $line) {
            if ($line->getVatPercentage()->get() === $rate) {
                return $line;
            }
        }

        return null;
    }

    private function validateLines(array $vatLines): void
    {
        foreach ($vatLines as $line) {
            if (! $line instanceof VatAllocatedLine) {
                throw new \InvalidArgumentException('vatLines must be an array of VatAllocatedLine instances.');
            }
        }
    }

    /** @param VatAllocatedLine[] $vatLines */
    private function validateTotals(array $vatLines, Money $totalExcluding, Money $totalVat, Money $totalIncluding): void
    {
        if (! $totalExcluding->add($totalVat)->equals($totalIncluding)) {
            throw new InvalidVatAllocatedTotal('VAT allocated total must satisfy excluding VAT + VAT = including VAT.');
        }

        $lineExcluding = new Money('0', $totalExcluding->getCurrency());
        $lineVat = new Money('0', $totalVat->getCurrency());

        foreach ($vatLines as $line) {
            $lineExcluding = $lineExcluding->add($line->getTaxableBase());
            $lineVat = $lineVat->add($line->getVatAmount());
        }

        if (! $lineExcluding->equals($totalExcluding)) {
            throw new InvalidVatAllocatedTotal('VAT line taxable bases must equal the allocated total excluding VAT.');
        }

        if (! $lineVat->equals($totalVat)) {
            throw new InvalidVatAllocatedTotal('VAT line amounts must equal the allocated total VAT.');
        }
    }
}
