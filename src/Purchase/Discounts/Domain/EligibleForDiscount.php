<?php

namespace Optiphar\Discounts;

use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Purchase\Cart\CartDiscount;
use Money\Money;

interface EligibleForDiscount
{
    public function discountBasePriceAsMoney(array $conditions): Money;

    public function discountTotalAsMoney(): Money;

    public function discounts(): Collection;

    public function addDiscount(CartDiscount $discount);
}
