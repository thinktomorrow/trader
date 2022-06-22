<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Cash;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;

interface Price
{
    public static function fromScalars(string|int $amount, string $taxRate, bool $includesVat): static;

    public static function fromPrice(self $otherPrice): static;

    public static function fromMoney(Money $money, TaxRate $taxRate, bool $includesVat): static;

    public static function zero(): static;

    public function getMoney(): Money;

    public function getIncludingVat(): Money;

    public function getExcludingVat(): Money;

    public function getTaxRate(): TaxRate;

    public function getTaxTotal(): Money;

    public function includesVat(): bool;

    public function multiply(int $quantity): static;

    public function add(Price $otherPrice): static;

    public function subtract(Price $otherPrice): static;

    public function changeTaxRate(TaxRate $taxRate): static;

    /** Allows to add a price with a potential different tax rate. */
    public function addDifferent(Price $otherPrice): static;

    /** Allows to subtract a price with a potential different tax rate. */
    public function subtractDifferent(Price $otherPrice): static;
}
