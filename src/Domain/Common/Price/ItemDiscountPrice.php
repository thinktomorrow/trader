<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

interface ItemDiscountPrice extends Price
{
    public static function fromExcludingVat(Money $amount, VatPercentage $vatPercentage): static;

    public static function fromIncludingVat(Money $includingVat, VatPercentage $vatPercentage): static;

    public static function zero(VatPercentage $vatPercentage, bool $includingVatAuthoritative = false): static;

    public function multiply(int $quantity): static;

    public function add(ItemDiscountPrice $discountPrice): static;

    public function getIncludingVat(): Money;

    public function getVatPercentage(): VatPercentage;

    public function isIncludingVatAuthoritative(): bool;
}
