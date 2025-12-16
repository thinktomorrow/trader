<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;

interface TotalPrice extends Price
{
    public static function fromExcludingVat(Money $excludingVat): static;

    public static function zero(): static;

    public function add(Price $otherPrice): static;

    public function subtract(Price $otherPrice): static;
}
