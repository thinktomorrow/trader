<?php

namespace Thinktomorrow\Trader\Order\Domain;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;

class Order
{
    const STATUS_NEW = 1;

    private $id;
    private $items;
    private $discounts; // order level applied discounts
    private $discountTotal;

    public function __construct()
    {
        $this->items = new ItemCollection;
        $this->discounts = new AppliedDiscountCollection;
        $this->discountTotal = Money::EUR(0); // TODO set currency outside of class
    }

    public function id()
    {
        return $this->id;
    }

    public function items(): ItemCollection
    {
        return $this->items;
    }

    public function discounts(): AppliedDiscountCollection
    {
        return $this->discounts;
    }

    /**
     * Add applied discounts
     *
     * @param $discount
     */
    public function addDiscount(AppliedDiscount $discount)
    {
        $this->discounts->add($discount);
    }

    public function subtotal(): Money
    {
        return array_reduce($this->items->all(), function($carry, Item $item){
            return $carry->add($item->total());
        },Money::EUR(0)); // TODO currency should be changeable
    }

    public function discountTotal(): Money
    {
        return $this->discountTotal;
    }

    public function addToDiscountTotal(Money $addition)
    {
        $this->discountTotal = $this->discountTotal->add($addition);
    }

    public function total(): Money
    {
        return $this->subtotal()
                    ->subtract($this->discountTotal());
    }

    public function shipmentId()
    {
        // shipment method
    }

    public function paymentId()
    {
        // payment method
    }

    // customer
    // email
    // addresses
    // shipment method
    // payment method
}