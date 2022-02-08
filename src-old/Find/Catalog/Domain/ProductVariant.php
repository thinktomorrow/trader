<?php

namespace Find\Catalog\Domain;

use Money\Money;
use Common\Domain\Taxes\TaxRate;
use Purchase\Items\Domain\PurchasableItem;

interface ProductVariant extends PurchasableItem
{
    public function id(): ProductVariantId;

    public function productId(): ProductId;

    public function salePrice(): Money;

    public function taxRate(): TaxRate;
}
