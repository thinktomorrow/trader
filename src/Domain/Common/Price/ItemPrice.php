<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

interface ItemPrice extends Price
{
    public static function fromExcludingVat(Money $amount, VatPercentage $vatPercentage): static;

    public static function fromMoney(Money $amount, VatPercentage $vatPercentage, bool $includesVat): static;

    public static function fromScalars(int|string $amount, string $vatPercentage, bool $includesVat): static;

    public function getVatPercentage(): VatPercentage;

    public function multiply(int $quantity): static;

    public function applyDiscount(ItemDiscount $discount): static;

    public function changeVatPercentage(VatPercentage $vatPercentage): static;

    public function hasOriginalIncludingVat(): bool;
}
