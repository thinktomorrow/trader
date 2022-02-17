<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Cash;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;

interface Price
{
    public static function fromScalars(string|int $amount, string $currency, string $taxRate, bool $includesTax): static;

    public static function fromPrice(self $otherPrice): static;

    public static function fromMoney(Money $money, TaxRate $taxRate, bool $includesTax): static;

    public static function zero(): static;

    public function getIncludingVat(): Money;

    public function getExcludingVat(): Money;

    public function getTaxRate(): TaxRate;

    public function includesTax(): bool;

    public function multiply(int $quantity): static;

    public function add(Price $otherPrice): static;

    public function subtract(Price $otherPrice): static;
}
