<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;

interface DiscountPrice extends Price
{
    public static function fromExcludingVat(Money $amount): static;

    public static function zero(): static;

    public function add(DiscountPrice $discountPrice): static;
}
