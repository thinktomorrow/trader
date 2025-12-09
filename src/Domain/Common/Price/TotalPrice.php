<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;

interface TotalPrice
{
    public static function fromCalculated(Money $includingVat, Money $excludingVat): static;

    public static function zero(): static;

    public function getIncludingVat(): Money;

    public function getExcludingVat(): Money;

    public function getVatTotal(): Money;

    public function add(ItemPrice|TotalPrice $otherPrice): static;

    public function subtract(ItemPrice|TotalPrice $otherPrice): static;
}
