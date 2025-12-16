<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

/**
 * Value object representing a calculated price where the canonical state is:
 *   - excluding VAT amount
 *   - VAT percentage
 *
 * Domain logic:
 * - The canonical state is always excluding VAT.
 * - Including VAT and VAT total are always derived from the canonical state
 * - In case the price is constructed from an including VAT amount, that original
 *   amount is stored to avoid rounding drift when retrieving including VAT again.
 * - Multiplication should be done on the excluding VAT amount to avoid rounding drift.
 * - Discount should be applied to the entire line total, not per unit.
 * - ItemPrice should handle VAT correctness.
 */
interface ItemPrice extends PriceWithVat
{
    public static function fromExcludingVat(Money $amount, VatPercentage $vatPercentage): static;

    public static function fromMoney(Money $amount, VatPercentage $vatPercentage, bool $includesVat): static;

    public static function fromScalars(int|string $amount, string $vatPercentage, bool $includesVat): static;

    public function getVatPercentage(): VatPercentage;

    public function multiply(int $quantity): static;

    public function applyDiscount(DiscountPrice $discount): static;

    public function changeVatPercentage(VatPercentage $vatPercentage): static;

    public function hasOriginalIncludingVat(): bool;
}
