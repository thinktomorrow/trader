<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Items\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Taxes\TaxRate;

interface PurchasableItem
{
    /**
     * Unique reference to the purchasable item
     * @return mixed
     */
    public function purchasableItemId(): PurchasableItemId;

    /**
     * Price at which this item can be purchased
     * @return Money
     */
    public function salePrice(): Money;

    /**
     * @return TaxRate
     */
    public function taxRate(): TaxRate;

    /**
     * All the information required for the purchase of this item.
     * This allows to refer to historical accurate item data.
     *
     * @return array
     */
    public function cartItemData(): array;
}
