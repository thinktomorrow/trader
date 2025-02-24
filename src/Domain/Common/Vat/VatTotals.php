<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Vat;

use Assert\Assertion;
use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Cash\PreciseMoney;

class VatTotals
{
    /** @var VatTotal[] */
    private iterable $vatTotals;

    private function __construct(iterable $vatTotals)
    {
        Assertion::allIsInstanceOf((array)$vatTotals, VatTotal::class);

        $this->vatTotals = $vatTotals;
    }

    public static function fromVatApplicables(array $vatApplicables): static
    {
        Assertion::allIsInstanceOf($vatApplicables, VatApplicable::class);

        return new static(static::convertVatApplicablesToTotals($vatApplicables));
    }

    public static function zero(): static
    {
        return new static([]);
    }

    public function addVatApplicableTotal(VatPercentage $vatPercentage, VatApplicableTotal $taxableTotal): static
    {
        $vatTotals = $this->vatTotals;

        $match = false;
        foreach ($vatTotals as $i => $vatTotal) {
            if ($vatTotal->getVatPercentage()->equals($vatPercentage)) {
                $vatTotals[$i] = $vatTotal->add($taxableTotal);
                $match = true;
            }
        }

        if (! $match) {
            $vatTotals[] = new VatTotal($vatPercentage, $taxableTotal);
        }

        return new static($vatTotals);
    }

    public function subtractVatApplicableTotal(VatPercentage $vatPercentage, VatApplicableTotal $vatApplicableTotal): static
    {
        $vatTotals = $this->vatTotals;

        $match = false;
        foreach ($vatTotals as $i => $vatTotal) {
            if ($vatTotal->getVatPercentage()->equals($vatPercentage)) {
                $vatTotals[$i] = $vatTotal->subtract($vatApplicableTotal);
                $match = true;
            }
        }

        if (! $match) {
            $vatTotals[] = new VatTotal($vatPercentage, $vatApplicableTotal->negative());
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

    public function getVatApplicableTotal(): Money
    {
        $total = array_reduce(
            $this->vatTotals,
            fn (VatApplicableTotal $carry, VatTotal $vatTotal) => $carry->add($vatTotal->getVatApplicableTotal()),
            VatApplicableTotal::zero(VatTotal::VAT_CALCULATION_PRECISION)
        );

        return $total->getMoney();
    }

    public function getVatTotal(): Money
    {
        $total = array_reduce(
            $this->vatTotals,
            fn ($carry, VatTotal $taxRateTotal) => $carry->add($taxRateTotal->getPreciseVatTotal()->getPreciseMoney()),
            Cash::zero()
        );

        $total = PreciseMoney::fromMoney($total)->getMoney();

        if ($total->isNegative()) {
            return new Money(0, $total->getCurrency());
        }

        return $total;
    }

    /**
     * For each rate the item totals are added up. The tax amount is then calculated for each total.
     * If we would take the tax amount of each item, the tax total is prone to rounding errors.
     *
     * @return array
     */
    private static function convertVatApplicablesToTotals(iterable $vatApplicables): array
    {
        /** @var VatTotal[] $totalsPerRate */
        $totalsPerRate = [];

        /** @var VatApplicable $taxable */
        foreach ($vatApplicables as $vatApplicable) {
            $key = $vatApplicable->getVatPercentage()->toPercentage()->get();

            if (! isset($totalsPerRate[$key])) {
                $totalsPerRate[$key] = VatTotal::zero($vatApplicable->getVatPercentage());
            }

            $totalsPerRate[$key] = $totalsPerRate[$key]->add($vatApplicable->getVatApplicableTotal());
        }

        return $totalsPerRate;
    }
}
