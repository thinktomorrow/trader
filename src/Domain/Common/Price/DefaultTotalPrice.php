<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;

class DefaultTotalPrice implements TotalPrice
{
    private Money $includingVat;
    private Money $excludingVat;

    private function __construct(Money $includingVat, Money $excludingVat)
    {
        if ($includingVat->isNegative()) {
            throw new PriceCannotBeNegative('Including VAT money amount cannot be negative: ' . $includingVat->getAmount() . ' is given.');
        }

        if ($excludingVat->isNegative()) {
            throw new PriceCannotBeNegative('Excluding VAT money amount cannot be negative: ' . $excludingVat->getAmount() . ' is given.');
        }

        $this->includingVat = $includingVat;
        $this->excludingVat = $excludingVat;
    }

    public static function fromCalculated(Money $includingVat, Money $excludingVat): static
    {
        return new static(
            $includingVat,
            $excludingVat,
        );
    }

    public static function zero(): static
    {
        return new static(Cash::zero(), Cash::zero());
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

    public function add(ItemPrice|TotalPrice $otherPrice): static
    {
        return new static(
            $this->includingVat->add($otherPrice->getIncludingVat()),
            $this->excludingVat->add($otherPrice->getExcludingVat()),
        );
    }

    public function subtract(ItemPrice|TotalPrice $otherPrice): static
    {
        return new static(
            $this->includingVat->subtract($otherPrice->getIncludingVat()),
            $this->excludingVat->subtract($otherPrice->getExcludingVat()),
        );
    }
}
