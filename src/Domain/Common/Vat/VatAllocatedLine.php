<?php

namespace Thinktomorrow\Trader\Domain\Common\Vat;

use Money\Money;

/**
 * One VAT line represents the taxable base + VAT amount
 * for one specific VAT percentage.
 *
 * - taxableBase = excluding VAT
 * - vatAmount   = VAT amount for this rate
 * - total       = taxableBase + vatAmount
 */
final class VatAllocatedLine
{
    private Money $taxableBase;
    private Money $vatAmount;
    private VatPercentage $vatPercentage;

    public function __construct(Money $taxableBase, Money $vatAmount, VatPercentage $vatPercentage)
    {
        $this->taxableBase = $taxableBase;
        $this->vatAmount = $vatAmount;
        $this->vatPercentage = $vatPercentage;
    }

    public function getTaxableBase(): Money
    {
        return $this->taxableBase;
    }

    public function getVatAmount(): Money
    {
        return $this->vatAmount;
    }

    public function getVatPercentage(): VatPercentage
    {
        return $this->vatPercentage;
    }

    public function getTotalIncludingVat(): Money
    {
        return $this->taxableBase->add($this->vatAmount);
    }

    public function add(VatAllocatedLine $other): static
    {
        if (!$this->vatPercentage->equals($other->vatPercentage)) {
            throw new \InvalidArgumentException('Cannot add VatAllocatedLine with different VAT percentages. ' .
                'Given: ' . $other->vatPercentage->get() . '%, expected: ' . $this->vatPercentage->get() . '%.');
        }

        return new static(
            $this->taxableBase->add($other->taxableBase),
            $this->vatAmount->add($other->vatAmount),
            $this->vatPercentage
        );
    }

    public function subtract(VatAllocatedLine $other): static
    {
        if (!$this->vatPercentage->equals($other->vatPercentage)) {
            throw new \InvalidArgumentException('Cannot subtract VatAllocatedLine with different VAT percentages. ' .
                'Given: ' . $other->vatPercentage->get() . '%, expected: ' . $this->vatPercentage->get() . '%.');
        }

        return new static(
            $this->taxableBase->subtract($other->taxableBase),
            $this->vatAmount->subtract($other->vatAmount),
            $this->vatPercentage
        );
    }
}
