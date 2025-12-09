<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

/**
 * Value object representing a calculated price with both including and excluding VAT amounts.
 * This class is immutable and avoids rounding issues by storing both amounts directly.
 */
class DefaultItemPrice implements ItemPrice
{
    private Money $includingVat;
    private Money $excludingVat;
    private VatPercentage $vatPercentage;

    private function __construct(Money $includingVat, Money $excludingVat, VatPercentage $vatPercentage)
    {
        if ($includingVat->isNegative()) {
            throw new PriceCannotBeNegative('Including VAT money amount cannot be negative: ' . $includingVat->getAmount() . ' is given.');
        }

        if ($excludingVat->isNegative()) {
            throw new PriceCannotBeNegative('Excluding VAT money amount cannot be negative: ' . $excludingVat->getAmount() . ' is given.');
        }

        $this->includingVat = $includingVat;
        $this->excludingVat = $excludingVat;
        $this->vatPercentage = $vatPercentage;

        $this->validateVatPercentage();
    }

    public static function fromCalculated(Money $includingVat, Money $excludingVat, VatPercentage $vatPercentage): static
    {
        return new static(
            $includingVat,
            $excludingVat,
            $vatPercentage
        );
    }

    public function getIncludingVat(): Money
    {
        return $this->includingVat;
    }

    public function getExcludingVat(): Money
    {
        return $this->excludingVat;
    }

    public function getVatTotal(): Money
    {
        return $this->getIncludingVat()
            ->subtract($this->getExcludingVat());
    }

    public function getVatPercentage(): VatPercentage
    {
        return $this->vatPercentage;
    }

    public function multiply(int $quantity): static
    {
        return new static(
            $this->includingVat->multiply($quantity),
            $this->excludingVat->multiply($quantity),
            $this->vatPercentage
        );
    }

    public function applyDiscount(ItemDiscount $discount): static
    {
        return new static(
            $this->includingVat->subtract($discount->getIncludingVat()),
            $this->excludingVat->subtract($discount->getExcludingVat()),
            $this->vatPercentage
        );
    }

    private function validateVatPercentage(): void
    {
        $vatAmount = $this->getVatTotal()->getAmount();
        $exclAmount = $this->excludingVat->getAmount();

        if ($this->excludingVat->isZero()) {
            return;
        }

        $percentage = round(($vatAmount / $exclAmount) * 100, 0);

        $expectedPercentage = VatPercentage::fromString((string)$percentage);

        if (!$expectedPercentage->equals($this->vatPercentage)) {
            throw new \InvalidArgumentException('The provided VAT percentage [' . $this->vatPercentage->get() . '] does not match the calculated VAT [' . $expectedPercentage->get() . '] from the including and excluding amounts.');
        }
    }
}
