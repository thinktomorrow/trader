<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price\Old;

use Assert\Assertion;
use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

class VatTotals
{
    /** @var VatTotal[] */
    private iterable $vatTotals;

    private function __construct(iterable $vatTotals)
    {
        $this->validateVatTotals($vatTotals);

        $this->vatTotals = $vatTotals;
    }

    public static function zero(): static
    {
        return new static([]);
    }

    public function add(VatTotal $vatTotal): static
    {
        $vatTotals = $this->vatTotals;

        $match = false;
        foreach ($vatTotals as $i => $existingVatTotal) {
            if ($existingVatTotal->getVatPercentage()->equals($vatTotal->getVatPercentage())) {
                $vatTotals[$i] = $existingVatTotal->add($vatTotal->getTotal());
                $match = true;
            }
        }

        if (! $match) {
            $vatTotals[] = $vatTotal;
        }

        return new static($vatTotals);
    }

    public function subtract(VatTotal $vatTotal): static
    {
        $vatTotals = $this->vatTotals;

        foreach ($vatTotals as $i => $existingVatTotal) {
            if ($existingVatTotal->getVatPercentage()->equals($vatTotal->getVatPercentage())) {
                $vatTotals[$i] = $existingVatTotal->subtract($vatTotal->getTotal());
            }
        }

        return new static($vatTotals);
    }

    public function get(): iterable
    {
        return $this->vatTotals;
    }

    public function find(VatPercentage $vatPercentage): ?VatTotal
    {
        foreach ($this->vatTotals as $vatTotal) {
            if ($vatTotal->getVatPercentage()->equals($vatPercentage)) {
                return $vatTotal;
            }
        }

        return null;
    }

    public function getVatTotal(): Money
    {
        $total = array_reduce(
            $this->vatTotals,
            fn ($carry, VatTotal $vatTotal) => $carry->add($vatTotal->getTotal()),
            Cash::zero()
        );

        if ($total->isNegative()) {
            return new Money(0, $total->getCurrency());
        }

        return $total;
    }

    private function validateVatTotals(iterable $vatTotals): void
    {
        Assertion::allIsInstanceOf((array)$vatTotals, VatTotal::class);
    }
}
