<?php

namespace Thinktomorrow\Trader\Order\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Common\Domain\State\StatefulContract;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;
use Thinktomorrow\Trader\Order\Domain\Services\SumOfTaxes;

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
    private $defaultTaxPercentage;

    public function __construct(OrderId $id)
    {
        $this->id = $id;
        $this->items = new ItemCollection;
        $this->discounts = new AppliedDiscountCollection;
        $this->discountTotal = $this->shipmentTotal = $this->paymentTotal = Cash::make(0);

        $this->setDefaultTaxPercentage(Percentage::fromPercent(0));

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

    public function inCustomerHands(): bool
    {
        return (new OrderState($this))->inCustomerHands();
    }

    public function inMerchantHands(): bool
    {
        return (new OrderState($this))->inMerchantHands();
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
        },Cash::make(0)); // TODO currency should be changeable
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

    public function defaultTaxPercentage(): Percentage
    {
        return $this->defaultTaxPercentage;
    }

    public function setDefaultTaxPercentage(Percentage $taxPercentage)
    {
        $this->defaultTaxPercentage = $taxPercentage;
    }

    public function tax(): Money
    {
        return array_reduce($this->taxRates(),function($carry, $taxRate){
            return $carry->add($taxRate['tax']);
        },Cash::make(0));
    }

    /**
     * Collection of used taxRates and their resp. tax amount
     * TODO: add shipment and discount tax as well
     * @return array
     */
    public function taxRates(): array
    {
        return (new SumOfTaxes())->forOrder($this);

        // Global amounts such as discountTotal, shipmentTotal and PaymentTotal also have an inclusive tax.
        // This tax is the default one for this order
        // TODO: determine the default tax!!!! Default tax is the one set by the admin
        // e.g. new OrderTaxRate($defaultTaxRate,$this);
    }
}