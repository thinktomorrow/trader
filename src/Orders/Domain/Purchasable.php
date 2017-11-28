<?php

namespace Thinktomorrow\Trader\Orders\Domain;

use Money\Money;
use Thinktomorrow\Trader\Tax\Domain\TaxId;

interface Purchasable
{
    public function purchasableId(): PurchasableId;

    /**
     * Class name of the purchasable object.
     *
     * @return string
     */
    public function purchasableType(): string;

    /**
     * Collection of item details. Here you give custom attributes as name
     * description that should be available for the item model.
     *
     * @return array
     */
    public function itemData(): array;

    /**
     * The real unit price, with sales already applied.
     *
     * @return Money
     */
    public function salePrice(): Money;

    /**
     * Each purchasable has a reference to a TaxRate which has
     * the possibility to be altered during the checkout.
     *
     * @return TaxId
     */
    public function taxId(): TaxId;
}
