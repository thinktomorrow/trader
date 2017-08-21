<?php

namespace Thinktomorrow\Trader\Sales\Domain;

use Money\Money;

interface EligibleForSale
{
    public function price(): Money;

    public function salePrice(): Money;

    public function saleTotal(): Money;

    public function addToSaleTotal(Money $addition);

    public function sales(): array;

    public function addSale(AppliedSale $sale);
}
