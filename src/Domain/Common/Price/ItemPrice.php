<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

interface ItemPrice
{
    public static function fromCalculated(Money $includingVat, Money $excludingVat, VatPercentage $vatPercentage): static;

    public function getIncludingVat(): Money;

    public function getExcludingVat(): Money;

    public function getVatTotal(): Money;

    public function getVatPercentage(): VatPercentage;

    public function multiply(int $quantity): static;

    public function applyDiscount(ItemDiscount $discount): static;
}
