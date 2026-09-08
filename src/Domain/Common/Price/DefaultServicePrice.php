<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Price\Exceptions\PriceCannotBeNegative;

/**
 * {@inheritdoc}
 */
class DefaultServicePrice implements ServicePrice
{
    private Money $excludingVat;

    private ?Money $includingVat;

    private function __construct(Money $excludingVat, ?Money $includingVat = null)
    {
        if ($excludingVat->isNegative()) {
            throw new PriceCannotBeNegative(
                'Excluding VAT money amount cannot be negative: '.$excludingVat->getAmount().' is given.'
            );
        }

        $this->excludingVat = $excludingVat;
        $this->includingVat = $includingVat;
    }

    public static function fromExcludingVat(Money $excludingVat): static
    {
        return new static($excludingVat);
    }

    public static function fromIncludingVat(Money $includingVat, Money $resolvedExcludingVat): static
    {
        if ($includingVat->isNegative()) {
            throw new PriceCannotBeNegative(
                'Including VAT money amount cannot be negative: '.$includingVat->getAmount().' is given.'
            );
        }

        return new static($resolvedExcludingVat, $includingVat);
    }

    public static function fromVatApplicableAmount(VatApplicableAmount $amount, Money $resolvedExcludingVat): static
    {
        return $amount->getTaxMode() === TaxMode::Inclusive
            ? static::fromIncludingVat($amount->getAmount(), $resolvedExcludingVat)
            : static::fromExcludingVat($resolvedExcludingVat);
    }

    public function getExcludingVat(): Money
    {
        return $this->excludingVat;
    }

    public function applyDiscount(DiscountPrice $discount): static
    {
        $newExcluding = $this->excludingVat->subtract($discount->getExcludingVat());

        if ($newExcluding->isNegative()) {
            throw new PriceCannotBeNegative(
                'Applying the discount would result in a negative excluding VAT amount: '.
                $newExcluding->getAmount()
            );
        }

        if ($this->includingVat !== null && $discount->getTaxMode() === TaxMode::Inclusive) {
            $newIncluding = $this->includingVat->subtract($discount->getAuthoritativeAmount());

            if ($newIncluding->isNegative()) {
                throw new PriceCannotBeNegative(
                    'Applying the discount would result in a negative including VAT amount: '.$newIncluding->getAmount()
                );
            }

            return static::fromIncludingVat($newIncluding, $newExcluding);
        }

        return new static($newExcluding);
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
