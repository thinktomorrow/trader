<?php

namespace Thinktomorrow\Trader\Order\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Tax\Domain\TaxId;

interface Purchasable
{
    /**
     * Unique item identifier - usually the SKU or primary key.
     *
     * @return ItemId
     */
    public function itemId(): ItemId;

    /**
     * Collection of item details. Here you pass
     * custom attributes as name, description.
     *
     * @return array
     */
    public function itemData(): array;

    /**
     * Unit price inclusive tax.
     *
     * @return Money
     */
    public function price(): Money;

    /**
     * Discounted unit price.
     *
     * @return Money
     */
    public function salePrice(): Money;

    /**
     * @return Percentage
     */
    public function taxRate(): Percentage;

    /**
     * Each purchasable has a reference to a TaxRate which has
     * the possibility to be altered during the checkout.
     *
     * @return TaxId
     */
    public function taxId(): TaxId;
}
