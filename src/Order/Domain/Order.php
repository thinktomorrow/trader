<?php

namespace Thinktomorrow\Trader\Order\Domain;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;

final class Order
{
    const STATUS_NEW = 1;

    private $id;
    private $items;
    private $discounts; // order level applied discounts
    private $discountTotal;
    private $shipmentTotal;

    private $shipmentMethodId;
    private $shipmentRuleId;

    public function __construct(OrderId $id)
    {
        $this->id = $id;
        $this->items = new ItemCollection;
        $this->discounts = new AppliedDiscountCollection;
        $this->discountTotal = Money::EUR(0); // TODO set currency outside of class
        $this->shipmentTotal = Money::EUR(0); // TODO set currency outside of class
    }

    public function id(): OrderId
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

    public function shipmentTotal(): Money
    {
        return $this->shipmentTotal;
    }

    public function shipmentMethodId(): ShipmentMethodId
    {
        return $this->shipmentMethodId;
    }

    public function shipmentRuleId(): ShipmentRuleId
    {
        return $this->shipmentRuleId;
    }

    public function setShipment(ShipmentMethodId $shipmentMethodId, ShipmentRuleId $shipmentRuleId)
    {
        $this->shipmentMethodId = $shipmentMethodId;
        $this->shipmentRuleId = $shipmentRuleId;
    }

    public function setShipmentTotal(Money $shipmentTotal)
    {
        $this->shipmentTotal = $shipmentTotal;

        return $this;
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