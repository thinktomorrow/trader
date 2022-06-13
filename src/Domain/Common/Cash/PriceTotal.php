<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Cash;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRateTotals;

/**
 * The total of multiple prices combined. This can refer to a subtotal of a cart where
 * multiple prices with different tax rates are possible. This PriceTotal takes care
 * of these combinations to provide a consistent api for tax behaviour.
 */
interface PriceTotal
{
    public static function make(Money $money, TaxRateTotals $taxRateTotals, bool $includesVat): static;

    public static function zero(): static;

    public function getIncludingVat(): Money;

    public function getExcludingVat(): Money;

    public function getTaxRateTotals(): TaxRateTotals;

    public function includesVat(): bool;

    public function add(Price $price): static;

    public function subtract(Price $price): static;
}
