<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price\Old;

use Money\Money;

/**
 * The total of multiple prices combined. This can refer to a subtotal of a cart where
 * multiple prices with different tax rates are possible. This PriceTotal takes care
 * of these combinations to provide a consistent api for tax behaviour.
 */
interface PriceTotal
{
    public static function make(Money $money, VatTotals $vatTotals, bool $includesVat): static;

    public static function zero(): static;

    public function getMoney(): Money;

    public function getIncludingVat(): Money;

    public function getExcludingVat(): Money;

    public function getVatTotals(): VatTotals;

    public function includesVat(): bool;

    public function add(Price $price): static;

    public function subtract(Price $price): static;
}
