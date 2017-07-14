<?php

namespace Thinktomorrow\Trader\Order\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Domain\State\StatefulContract;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;
use Thinktomorrow\Trader\Order\Domain\Services\SumOfItemTaxes;

final class Order implements StatefulContract
{
    use PayableAndShippable;

    /**
     * Current state
     * @var string
     */
    private $state;

    private $id;
    private $items;
    private $discounts; // order level applied discounts
    private $discountTotal;

    public function __construct(OrderId $id)
    {
        $this->id = $id;
        $this->items = new ItemCollection;
        $this->discounts = new AppliedDiscountCollection;
        $this->discountTotal = Cash::CUR(0); // TODO set currency outside of class
        $this->shipmentTotal = Cash::CUR(0); // TODO set currency outside of class
        $this->paymentTotal = Cash::CUR(0); // TODO set currency outside of class

        // Initial order state
        $this->state = OrderState::STATE_NEW;

        // TODO IncompleteOrderStatus
    }

    public function id(): OrderId
    {
        return $this->id;
    }

    public function state(): string
    {
        return $this->state;
    }

    public function changeState($state)
    {
        OrderState::assertNewState($this, $state);

        $this->state = $state;
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
        },Cash::CUR(0)); // TODO currency should be changeable
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
                    ->subtract($this->discountTotal())
                    ->add($this->paymentTotal())
                    ->add($this->shipmentTotal());
    }

    public function tax(): Money
    {
        return array_reduce($this->taxRates(),function($carry, $taxRate){
            return $carry->add($taxRate['tax']);
        },Cash::CUR(0));
    }

    /**
     * Collection of used taxRates and their resp. tax() amount
     * With roundings each item would add up to 295 but if subtotals are added we have a more precise tax per taxrate.

     * @return array
     */
    public function taxRates(): array
    {
        return (new SumOfItemTaxes())->forItems($this->items());
    }
}