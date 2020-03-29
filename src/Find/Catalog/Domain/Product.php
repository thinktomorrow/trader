<?php

namespace Thinktomorrow\Trader\Find\Catalog\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Taxes\TaxRate;
use Thinktomorrow\Trader\Purchase\Items\Domain\PurchasableItem;

interface Product extends PurchasableItem
{
    public function id();

    public function price(): Money;

    public function taxRate(): TaxRate;
}
