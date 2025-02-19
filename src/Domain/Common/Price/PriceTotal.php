<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Vat\VatTotals;

/**
 * The total of multiple prices combined. This can refer to a subtotal of a cart where
 * multiple prices with different tax rates are possible. This PriceTotal takes care
 * of these combinations to provide a consistent api for tax behaviour.
 */
interface PriceTotal extends ConvertsToMoney
{
    public static function make(Money $money, VatTotals $vatTotals, bool $includesVat): static;

    public static function zero(): static;

    public function getVatTotals(): VatTotals;

    public function includesVat(): bool;

    public function add(Price $price): static;

    public function subtract(Price $price): static;
}
