<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\Exceptions\PriceCannotBeNegative;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

class DefaultItemPrice implements ItemPrice
{
    private function __construct(
        private Money $excludingVat,
        private Money $includingVat,
        private VatPercentage $vatPercentage,
        private TaxMode $taxMode,
    ) {
        $this->assertValidAmounts($excludingVat, $includingVat);
    }

    public static function fromExcludingVat(Money $amount, VatPercentage $vatPercentage): static
    {
        return new static(
            $amount,
            Cash::from($amount)->addPercentage($vatPercentage->toPercentage()),
            $vatPercentage,
            TaxMode::Exclusive,
        );
    }

    public static function fromMoney(Money $amount, VatPercentage $vatPercentage, bool $includesVat): static
    {
        if ($includesVat) {
            return new static(
                Cash::from($amount)->subtractTaxPercentage($vatPercentage->toPercentage()),
                $amount,
                $vatPercentage,
                TaxMode::Inclusive,
            );
        }

        return static::fromExcludingVat($amount, $vatPercentage);
    }

    public static function fromScalars(int|string $amount, string $vatPercentage, bool $includesVat): static
    {
        return static::fromMoney(
            Cash::make($amount),
            VatPercentage::fromString($vatPercentage),
            $includesVat,
        );
    }

    public static function fromResolvedAmounts(Money $excludingVat, Money $includingVat, VatPercentage $vatPercentage, TaxMode $taxMode): static
    {
        return new static($excludingVat, $includingVat, $vatPercentage, $taxMode);
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
        return $this->includingVat->subtract($this->excludingVat);
    }

    public function getVatPercentage(): VatPercentage
    {
        return $this->vatPercentage;
    }

    public function add(ItemPrice $price): static
    {
        $this->assertSameVatPercentage($price);

        return static::fromResolvedAmounts(
            $this->excludingVat->add($price->getExcludingVat()),
            $this->includingVat->add($price->getIncludingVat()),
            $this->vatPercentage,
            $this->taxMode === $price->getTaxMode() ? $this->taxMode : TaxMode::Exclusive,
        );
    }

    public function subtract(ItemPrice $price): static
    {
        $this->assertSameVatPercentage($price);

        return static::fromResolvedAmounts(
            $this->excludingVat->subtract($price->getExcludingVat()),
            $this->includingVat->subtract($price->getIncludingVat()),
            $this->vatPercentage,
            $this->taxMode === $price->getTaxMode() ? $this->taxMode : TaxMode::Exclusive,
        );
    }

    public function multiply(int $quantity): static
    {
        if ($this->taxMode === TaxMode::Exclusive) {
            return static::fromExcludingVat(
                $this->excludingVat->multiply($quantity),
                $this->vatPercentage,
            );
        }

        return static::fromResolvedAmounts(
            $this->excludingVat->multiply($quantity),
            $this->includingVat->multiply($quantity),
            $this->vatPercentage,
            $this->taxMode,
        );
    }

    public function applyDiscount(ItemDiscountPrice $discount): static
    {
        if (! $this->vatPercentage->equals($discount->getVatPercentage())) {
            throw new \InvalidArgumentException(
                'Cannot apply ItemDiscountPrice with different VAT percentages ('.
                $discount->getVatPercentage()->get().'% given, '.
                $this->vatPercentage->get().'% expected).'
            );
        }

        return static::fromResolvedAmounts(
            $this->excludingVat->subtract($discount->getExcludingVat()),
            $this->includingVat->subtract($discount->getIncludingVat()),
            $this->vatPercentage,
            $this->taxMode,
        );
    }

    public function changeVatPercentage(VatPercentage $vatPercentage): static
    {
        return $this->taxMode === TaxMode::Inclusive
            ? static::fromMoney($this->includingVat, $vatPercentage, true)
            : static::fromExcludingVat($this->excludingVat, $vatPercentage);
    }

    public function isIncludingVatAuthoritative(): bool
    {
        return $this->taxMode === TaxMode::Inclusive;
    }

    public function getTaxMode(): TaxMode
    {
        return $this->taxMode;
    }

    public function getAuthoritativeAmount(): Money
    {
        return $this->taxMode === TaxMode::Inclusive ? $this->includingVat : $this->excludingVat;
    }

    private function assertSameVatPercentage(ItemPrice $price): void
    {
        if (! $this->vatPercentage->equals($price->getVatPercentage())) {
            throw new \InvalidArgumentException(
                'Cannot combine ItemPrice with different VAT percentage ('.
                $price->getVatPercentage()->get().'% given, '.
                $this->vatPercentage->get().'% expected).'
            );
        }
    }

    private function assertValidAmounts(Money $excludingVat, Money $includingVat): void
    {
        if ($excludingVat->isNegative()) {
            throw new PriceCannotBeNegative('Excluding VAT money amount cannot be negative: '.$excludingVat->getAmount().' is given.');
        }

        if ($includingVat->isNegative()) {
            throw new PriceCannotBeNegative('Including VAT money amount cannot be negative: '.$includingVat->getAmount().' is given.');
        }

        if (! $excludingVat->getCurrency()->equals($includingVat->getCurrency())) {
            throw new \InvalidArgumentException('Excluding and including VAT amounts must use the same currency.');
        }
    }
}
