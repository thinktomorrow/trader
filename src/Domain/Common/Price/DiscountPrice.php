<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;

interface DiscountPrice extends HasAuthoritativeAmount, Price
{
    public static function fromExcludingVat(Money $amount): static;

    public static function fromIncludingVat(Money $includingVat, Money $resolvedExcludingVat): static;

    public static function zero(TaxMode $taxMode = TaxMode::Exclusive): static;

    public function add(DiscountPrice $discountPrice): static;
}
