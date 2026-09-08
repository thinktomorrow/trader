<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;

/**
 * Value object representing the price of a service item (e.g. shipping or payment fees).
 *
 * Service items do NOT carry their own VAT percentage, nor can they derive a VAT-inclusive amount
 * in isolation. Their VAT is determined at the level of the order as a whole through a pro-rata
 * allocation across all applicable VAT rates of the products inside the order.
 *
 * Domain logic:
 * - A resolved excluding-VAT amount is always available for order calculations.
 * - The configured amount and its tax mode are retained as the authoritative source.
 * - A service item never has a VAT percentage of its own. VAT must be allocated later by the
 *   order's VAT allocation process (e.g. VatAllocator), based on the distribution of VAT rates
 *   of the order’s line items.
 * - Because service items may be split across multiple VAT rates, definitive including-VAT and
 *   VAT totals are exposed by the order-level VAT allocation result.
 * - As a value object, this class is immutable. All operations must return a new instance.
 *
 * In summary: a service price retains input authority while the VAT allocation owns tax totals.
 */
interface ServicePrice extends HasAuthoritativeAmount, Price
{
    public static function fromExcludingVat(Money $excludingVat): static;

    public static function fromIncludingVat(Money $includingVat, Money $resolvedExcludingVat): static;

    public static function fromVatApplicableAmount(VatApplicableAmount $amount, Money $resolvedExcludingVat): static;

    public function applyDiscount(DiscountPrice $discount): static;
}
