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
 * Service item prices represent pure net amounts which only later become taxable amounts
 * once they are processed through the order-level VAT allocation mechanism.
 *
 * Domain logic:
 * - The canonical state of a service price is ALWAYS the price excluding VAT.
 * - A service item never has a VAT percentage of its own. VAT must be allocated later by the
 *   order's VAT allocation process (e.g. VatAllocator), based on the distribution of VAT rates
 *   of the order’s line items.
 * - Because service items may need to be split across multiple VAT rates (when an order contains
 *   products with mixed VAT percentages), this value object intentionally exposes only the
 *   excluding-VAT price and not an including-VAT or VAT-total amount.
 * - As a value object, this class is immutable. All operations must return a new instance.
 *
 * In summary: service item prices represent pure net amounts which only later become taxable
 * amounts once they are processed through the order-level VAT allocation mechanism.
 */
interface ServicePrice extends Price
{
    public static function fromExcludingVat(Money $excludingVat): static;

    public function applyDiscount(DiscountPrice $discount): static;
}
