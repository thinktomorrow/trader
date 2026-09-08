<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\Exceptions\PriceCannotBeNegative;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

class DefaultItemDiscountPrice implements ItemDiscountPrice
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

    public static function fromIncludingVat(Money $includingVat, VatPercentage $vatPercentage): static
    {
        return new static(
            Cash::from($includingVat)->subtractTaxPercentage($vatPercentage->toPercentage()),
            $includingVat,
            $vatPercentage,
            TaxMode::Inclusive,
        );
    }

    public static function fromResolvedAmounts(Money $excludingVat, Money $includingVat, VatPercentage $vatPercentage, TaxMode $taxMode): static
    {
        return new static($excludingVat, $includingVat, $vatPercentage, $taxMode);
    }

    public static function zero(VatPercentage $vatPercentage, bool $includingVatAuthoritative = false): static
    {
        return new static(
            Cash::zero(),
            Cash::zero(),
            $vatPercentage,
            $includingVatAuthoritative ? TaxMode::Inclusive : TaxMode::Exclusive,
        );
    }

    public function getExcludingVat(): Money
    {
        return $this->excludingVat;
    }

    public function getIncludingVat(): Money
    {
        return $this->includingVat;
    }

    public function add(ItemDiscountPrice $discountPrice): static
    {
        if (! $this->vatPercentage->equals($discountPrice->getVatPercentage())) {
            throw new \InvalidArgumentException(
                'Cannot add ItemDiscountPrice with different VAT percentage ('.
                $discountPrice->getVatPercentage()->get().'% given, '.
                $this->vatPercentage->get().'% expected).'
            );
        }

        return static::fromResolvedAmounts(
            $this->excludingVat->add($discountPrice->getExcludingVat()),
            $this->includingVat->add($discountPrice->getIncludingVat()),
            $this->vatPercentage,
            $this->taxMode === $discountPrice->getTaxMode() ? $this->taxMode : TaxMode::Exclusive,
        );
    }

    public function multiply(int $quantity): static
    {
        return $this->taxMode === TaxMode::Inclusive
            ? static::fromIncludingVat($this->includingVat->multiply($quantity), $this->vatPercentage)
            : static::fromExcludingVat($this->excludingVat->multiply($quantity), $this->vatPercentage);
    }

    public function getVatPercentage(): VatPercentage
    {
        return $this->vatPercentage;
    }

    public function isIncludingVatAuthoritative(): bool
    {
        return $this->taxMode === TaxMode::Inclusive;
    }

    public function getVatTotal(): Money
    {
        return $this->includingVat->subtract($this->excludingVat);
    }

    public function getTaxMode(): TaxMode
    {
        return $this->taxMode;
    }

    public function getAuthoritativeAmount(): Money
    {
        return $this->taxMode === TaxMode::Inclusive ? $this->includingVat : $this->excludingVat;
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
