<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

trait PriceValue
{
    private Money $money;
    private VatPercentage $vatPercentage;
    private bool $includesVat;

    private function __construct(Money $money, VatPercentage $vatPercentage, bool $includesVat)
    {
        if ($money->isNegative()) {
            throw new PriceCannotBeNegative('Price money amount cannot be negative: ' . $money->getAmount() . ' is given.');
        }

        $this->money = $money;
        $this->vatPercentage = $vatPercentage;
        $this->includesVat = $includesVat;
    }

    public static function fromScalars(string|int $amount, string $vatPercentage, bool $includesVat): static
    {
        return new static(
            Cash::make($amount),
            VatPercentage::fromString($vatPercentage),
            $includesVat
        );
    }

    public static function zero(): static
    {
        // TODO: how to get default settings for this here?
        return new static(Cash::zero(), VatPercentage::fromString('0'), true);
    }

    public static function fromPrice(Price $otherPrice): static
    {
        return new static(
            $otherPrice->getMoney(),
            $otherPrice->getVatPercentage(),
            $otherPrice->includesVat()
        );
    }

    public static function fromMoney(Money $money, VatPercentage $vatPercentage, bool $includesVat): static
    {
        return new static($money, $vatPercentage, $includesVat);
    }

    public function getIncludingVat(): Money
    {
        if ($this->includesVat) {
            return $this->money;
        }

        return Cash::from($this->money)->addPercentage(
            $this->vatPercentage->toPercentage()
        );
    }

    public function getExcludingVat(): Money
    {
        if (! $this->includesVat) {
            return $this->money;
        }

        return Cash::from($this->money)->subtractTaxPercentage(
            $this->vatPercentage->toPercentage()
        );
    }

    public function getMoney(): Money
    {
        return $this->money;
    }

    public function includesVat(): bool
    {
        return $this->includesVat;
    }

    public function multiply(int $quantity): static
    {
        return static::fromMoney($this->money->multiply((string)$quantity), $this->vatPercentage, $this->includesVat);
    }

    public function add(Price $otherPrice): static
    {
        $this->assertSameTaxRates($otherPrice);

        $otherMoney = $this->includesVat()
            ? $otherPrice->getIncludingVat()
            : $otherPrice->getExcludingVat();

        return static::fromMoney($this->money->add($otherMoney), $this->vatPercentage, $this->includesVat);
    }

    public function subtract(Price $otherPrice): static
    {
        $this->assertSameTaxRates($otherPrice);

        $otherMoney = $this->includesVat()
            ? $otherPrice->getIncludingVat()
            : $otherPrice->getExcludingVat();

        return static::fromMoney($this->money->subtract($otherMoney), $this->vatPercentage, $this->includesVat);
    }

    public function addDifferent(Price $otherPrice): static
    {
        return $this->add(
            $otherPrice->changeVatPercentage($this->vatPercentage)
        );
    }

    public function subtractDifferent(Price $otherPrice): static
    {
        return $this->subtract(
            $otherPrice->changeVatPercentage($this->vatPercentage)
        );
    }

    public function getVatPercentage(): VatPercentage
    {
        return $this->vatPercentage;
    }

    public function getVatTotal(): Money
    {
        return $this->getIncludingVat()
            ->subtract($this->getExcludingVat());
    }

    public function changeVatPercentage(VatPercentage $vatPercentage): static
    {
        return static::fromMoney($this->getExcludingVat(), $vatPercentage, false);
    }

    private function assertSameTaxRates(Price $otherPrice): void
    {
        if (! $otherPrice->getVatPercentage()->equals($this->getVatPercentage())) {
            throw new PriceCannotContainMultipleTaxRates($otherPrice->getVatPercentage() . ' differs from expected ' . $this->getVatPercentage());
        }
    }
}
