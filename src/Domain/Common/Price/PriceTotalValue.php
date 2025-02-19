<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Vat\VatApplicableTotal;
use Thinktomorrow\Trader\Domain\Common\Vat\VatTotals;

trait PriceTotalValue
{
    private Money $money;
    private VatTotals $vatTotals;
    private bool $includesVat;

    private function __construct(Money $money, VatTotals $vatTotals, bool $includesVat)
    {
        if ($money->isNegative()) {
            throw new PriceCannotBeNegative('Price money amount cannot be negative: ' . $money->getAmount() . ' is given.');
        }

        $this->money = $money;
        $this->vatTotals = $vatTotals;
        $this->includesVat = $includesVat;
    }

    public static function make(Money $money, VatTotals $vatTotals, bool $includesVat): static
    {
        return new static(
            $money,
            $vatTotals,
            $includesVat
        );
    }

    public static function zero(): static
    {
        return new static(Cash::zero(), VatTotals::fromVatApplicables([]), true);
    }

    public function getIncludingVat(): Money
    {
        if ($this->includesVat) {
            return $this->money;
        }

        return $this->money->add(
            $this->vatTotals->getVatTotal()
        );
    }

    public function getExcludingVat(): Money
    {
        if (! $this->includesVat) {
            return $this->money;
        }

        return $this->money->subtract(
            $this->vatTotals->getVatTotal()
        );
    }

    public function getMoney(): Money
    {
        return $this->money;
    }

    public function getVatTotals(): VatTotals
    {
        return $this->vatTotals;
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

        $vatTotals = $this->vatTotals->addVatApplicableTotal($otherPrice->getVatPercentage(), $this->getVatApplicableTotal($otherPrice));

        return new static($this->money->add($otherMoney), $vatTotals, $this->includesVat);
    }

    public function subtract(Price $otherPrice): static
    {
        $otherMoney = $this->includesVat()
            ? $otherPrice->getIncludingVat()
            : $otherPrice->getExcludingVat();

        $vatTotals = $this->vatTotals->subtractVatApplicableTotal($otherPrice->getVatPercentage(), $this->getVatApplicableTotal($otherPrice));

        return new static($this->money->subtract($otherMoney), $vatTotals, $this->includesVat);
    }

    /**
     * Better precision for tax calculations since percentage divisions and multiplication is in effect
     * @param Price $otherPrice
     * @return VatApplicableTotal
     */
    protected function getVatApplicableTotal(Price $otherPrice): VatApplicableTotal
    {
        $exclusiveAmountAsFloat = $otherPrice->getIncludingVat()->getAmount() / ((float)$otherPrice->getVatPercentage()->toPercentage()->toDecimal() + 1);

        return VatApplicableTotal::calculateFromFloat($exclusiveAmountAsFloat);
    }
}
