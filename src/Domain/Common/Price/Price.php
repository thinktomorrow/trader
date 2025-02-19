<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

interface Price extends ConvertsToMoney
{
    public static function fromScalars(string|int $amount, string $vatPercentage, bool $includesVat): static;

    public static function fromPrice(self $otherPrice): static;

    public static function fromMoney(Money $money, VatPercentage $vatPercentage, bool $includesVat): static;

    public static function zero(): static;

    public function multiply(int $quantity): static;

    public function add(Price $otherPrice): static;

    public function subtract(Price $otherPrice): static;

    /** Allows to add a price with a potential different tax rate. */
    public function addDifferent(Price $otherPrice): static;

    /** Allows to subtract a price with a potential different tax rate. */
    public function subtractDifferent(Price $otherPrice): static;

    public function getVatPercentage(): VatPercentage;

    public function getVatTotal(): Money;

    public function changeVatPercentage(VatPercentage $vatPercentage): static;

    public function includesVat(): bool;
}
