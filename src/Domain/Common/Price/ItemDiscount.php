<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

interface ItemDiscount
{
    public static function fromExcludingVat(Money $amount, VatPercentage $vatPercentage): static;

    public static function fromMoney(Money $amount, VatPercentage $vatPercentage, bool $includesVat): static;

    public function getIncludingVat(): Money;

    public function getExcludingVat(): Money;

    public function getVatPercentage(): VatPercentage;

    public function getVatTotal(): Money;
}
