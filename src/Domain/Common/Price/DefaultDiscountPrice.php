<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\Exceptions\PriceCannotBeNegative;

class DefaultDiscountPrice implements DiscountPrice
{
    private Money $excludingVat;

    private ?Money $includingVat;

    private function __construct(Money $excludingVat, ?Money $includingVat = null)
    {
        if ($excludingVat->isNegative()) {
            throw new PriceCannotBeNegative('Excluding VAT money amount cannot be negative: '.$excludingVat->getAmount().' is given.');
        }

        $this->excludingVat = $excludingVat;
        $this->includingVat = $includingVat;
    }

    public static function fromExcludingVat(Money $amount): static
    {
        return new static($amount);
    }

    public static function fromIncludingVat(Money $includingVat, Money $resolvedExcludingVat): static
    {
        if ($includingVat->isNegative()) {
            throw new PriceCannotBeNegative('Including VAT money amount cannot be negative: '.$includingVat->getAmount().' is given.');
        }

        return new static($resolvedExcludingVat, $includingVat);
    }

    public static function zero(TaxMode $taxMode = TaxMode::Exclusive): static
    {
        return $taxMode === TaxMode::Inclusive
            ? static::fromIncludingVat(Cash::zero(), Cash::zero())
            : new static(Cash::zero());
    }

    public function getExcludingVat(): Money
    {
        return $this->excludingVat;
    }

    public function add(DiscountPrice $discountPrice): static
    {
        $excludingVat = $this->excludingVat->add($discountPrice->getExcludingVat());

        if ($this->includingVat !== null && $discountPrice->getTaxMode() === TaxMode::Inclusive) {
            return static::fromIncludingVat(
                $this->includingVat->add($discountPrice->getAuthoritativeAmount()),
                $excludingVat,
            );
        }

        return new static($excludingVat);
    }

    public function multiply(int $quantity): static
    {
        $excludingVat = $this->excludingVat->multiply($quantity);

        if ($this->includingVat !== null) {
            return static::fromIncludingVat($this->includingVat->multiply($quantity), $excludingVat);
        }

        return new static($excludingVat);
    }

    public function getTaxMode(): TaxMode
    {
        return $this->includingVat !== null ? TaxMode::Inclusive : TaxMode::Exclusive;
    }

    public function getAuthoritativeAmount(): Money
    {
        return $this->includingVat ?? $this->excludingVat;
    }
}
