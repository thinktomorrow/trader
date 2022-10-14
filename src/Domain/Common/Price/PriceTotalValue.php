<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxableTotal;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRateTotals;

trait PriceTotalValue
{
    private Money $money;
    private TaxRateTotals $taxRateTotals;
    private bool $includesVat;

    private function __construct(Money $money, TaxRateTotals $taxRateTotals, bool $includesVat)
    {
        if ($money->isNegative()) {
            throw new PriceCannotBeNegative('Price money amount cannot be negative: ' . $money->getAmount() . ' is given.');
        }

        $this->money = $money;
        $this->taxRateTotals = $taxRateTotals;
        $this->includesVat = $includesVat;
    }

    public static function make(Money $money, TaxRateTotals $taxRateTotals, bool $includesVat): static
    {
        return new static(
            $money,
            $taxRateTotals,
            $includesVat
        );
    }

    public static function zero(): static
    {
        return new static(Cash::zero(),TaxRateTotals::fromTaxables([]),true);
    }

    public function getIncludingVat(): Money
    {
        if ($this->includesVat) {
            return $this->money;
        }

        return $this->money->add(
            $this->taxRateTotals->getTaxTotal()
        );
    }

    public function getExcludingVat(): Money
    {
        if (! $this->includesVat) {
            return $this->money;
        }

        return $this->money->subtract(
            $this->taxRateTotals->getTaxTotal()
        );
    }

    public function getMoney(): Money
    {
        return $this->money;
    }

    public function getTaxRateTotals(): TaxRateTotals
    {
        return $this->taxRateTotals;
    }

    public function includesVat(): bool
    {
        return $this->includesVat;
    }

    public function add(Price $otherPrice): static
    {
        $otherMoney = $this->includesVat()
            ? $otherPrice->getIncludingVat()
            : $otherPrice->getExcludingVat();

        $taxRateTotals = $this->taxRateTotals->addTaxableTotal($otherPrice->getTaxRate(), $this->getTaxableTotal($otherPrice));

        return new static($this->money->add($otherMoney), $taxRateTotals, $this->includesVat);
    }

    public function subtract(Price $otherPrice): static
    {
        $otherMoney = $this->includesVat()
            ? $otherPrice->getIncludingVat()
            : $otherPrice->getExcludingVat();

        $taxRateTotals = $this->taxRateTotals->subtractTaxableTotal($otherPrice->getTaxRate(), $this->getTaxableTotal($otherPrice));

        return new static($this->money->subtract($otherMoney), $taxRateTotals, $this->includesVat);
    }

    /**
     * Better precision for tax calculations since percentage divisions and multiplication is in effect
     * @param Price $otherPrice
     * @return TaxableTotal
     */
    protected function getTaxableTotal(Price $otherPrice): TaxableTotal
    {
        $exclusiveAmountAsFloat = $otherPrice->getIncludingVat()->getAmount() / ((float)$otherPrice->getTaxRate()->toPercentage()->toDecimal() + 1);

        return TaxableTotal::calculateFromFloat($exclusiveAmountAsFloat);
    }
}
