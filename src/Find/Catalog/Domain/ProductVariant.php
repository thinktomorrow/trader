<?php

namespace Thinktomorrow\Trader\Find\Catalog\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Taxes\TaxRate;
use Thinktomorrow\Trader\Purchase\Items\Domain\PurchasableItem;

interface ProductVariant extends PurchasableItem
{
    public function id(): ProductVariantId;

    public function productId(): ProductId;

    public function salePrice(): Money;

    public function taxRate(): TaxRate;
}
