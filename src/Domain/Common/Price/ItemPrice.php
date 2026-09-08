<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

/**
 * Value object representing a price with resolved excluding and including VAT amounts.
 *
 * Domain logic:
 * - The tax mode determines which amount is authoritative.
 * - Multiplication uses the authoritative amount and derives its counterpart afterwards.
 * - Discount should be applied to the entire line total, not per unit.
 * - ItemPrice should handle VAT correctness.
 */
interface ItemPrice extends HasAuthoritativeAmount, PriceWithVat
{
    public static function fromExcludingVat(Money $amount, VatPercentage $vatPercentage): static;

    public static function fromMoney(Money $amount, VatPercentage $vatPercentage, bool $includesVat): static;

    public static function fromScalars(int|string $amount, string $vatPercentage, bool $includesVat): static;

    public static function fromResolvedAmounts(Money $excludingVat, Money $includingVat, VatPercentage $vatPercentage, TaxMode $taxMode): static;

    public function getVatPercentage(): VatPercentage;

    public function add(ItemPrice $price): static;

    public function subtract(ItemPrice $price): static;

    public function multiply(int $quantity): static;

    public function applyDiscount(ItemDiscountPrice $discount): static;

    public function changeVatPercentage(VatPercentage $vatPercentage): static;

    public function isIncludingVatAuthoritative(): bool;
}
