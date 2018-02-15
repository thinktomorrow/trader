<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Money\Money;

interface EligibleForDiscount
{
    public function discountBasePrice(): Money;

    public function discountTotal(): Money;

    public function addToDiscountTotal(Money $addition);

    public function discounts(): array;

    public function addDiscount(AppliedDiscount $discount);
}
