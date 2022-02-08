<?php declare(strict_types=1);

namespace Fulfil\Domain;

use Money\Money;

interface FulfillableItem
{
    /**
     * Unique reference to the item record
     * @return FulfillableItemId
     */
    public function fulfillableItemId(): FulfillableItemId;

    /**
     * All the information required for the fulfillment of this item.
     * This allows to refer to historical accurate item data.
     * e.g. when converting a cart to an order.
     *
     * @return array
     */
    public function fulfillableItemData(): array;

    public function total(): Money;

    public function discountTotal(): Money;

    public function subTotal(): Money;

    public function taxTotal(): Money;
}
