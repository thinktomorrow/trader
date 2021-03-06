<?php

namespace Thinktomorrow\Trader\Orders\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Config;
use Thinktomorrow\Trader\Common\Price\Cash;
use Thinktomorrow\Trader\Common\Price\Percentage;
use Thinktomorrow\Trader\Common\State\StatefulContract;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\EligibleForDiscount;
use Thinktomorrow\Trader\Orders\Domain\Services\SumOfTaxes;

final class Order implements StatefulContract, EligibleForDiscount
{
    use PayableAndShippable,
        HasShippingCost,
        HasPaymentCost;

    private $state;

    private $id; // Used by application layer (this is a uuid)
    private $persistenceId; // Used by persistence layer (db)
    private $reference;
    private $customerId;

    private $items;

    // Basket discounts - not including shipping and payment discounts.
    private $discounts = []; // basket level applied discounts
    private $discountTotal;
    private $discountPercentage;

    private $taxPercentage; // default tax percentage for order (shipment / payment)
    private $couponCode;

    public function __construct(OrderId $id)
    {
        $this->id = $id;
        $this->items = new ItemCollection();
        $this->discountTotal = Cash::make(0);
        $this->discountPercentage = Percentage::fromPercent(0);
        $this->setTaxPercentage(Percentage::fromPercent((new Config())->get('tax_percentage', 0)));

        // Initial order state
        $this->state = OrderState::NEW;

        // TODO IncompleteOrderStatus

        $this->shippingCost = new ShippingCost();
        $this->paymentCost = new PaymentCost();
    }

    public function id(): OrderId
    {
        return $this->id;
    }

    public function isPersisted(): bool
    {
        return !is_null($this->persistenceId);
    }

    public function setPersistenceId(int $persistenceId)
    {
        $this->persistenceId = $persistenceId;
    }

    public function persistenceId(): int
    {
        return $this->persistenceId;
    }

    /**
     * Unique merchant reference to order.
     *
     * @param string $reference
     */
    public function setReference(string $reference)
    {
        $this->reference = $reference;
    }

    public function hasReference(): bool
    {
        return (bool) $this->reference;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function customerId(): CustomerId
    {
        if (!$this->hasCustomer()) {
            throw new \RuntimeException('Requesting customer for order ['.$this->id()->get().'] but there is no customer attached.');
        }

        return $this->customerId;
    }

    public function hasCustomer(): bool
    {
        return !is_null($this->customerId);
    }

    public function setCustomerId(CustomerId $customerId)
    {
        $this->customerId = $customerId;
    }

    public function enteredCouponCode()
    {
        return $this->couponCode;
    }

    public function enterCouponCode($couponCode)
    {
        $this->couponCode = $couponCode;

        return $this;
    }

    public function state(): string
    {
        return $this->state;
    }

    public function changeState($state)
    {
        // Ignore change to current state - it should not trigger events either
        if ($state === $this->state) {
            return;
        }

        OrderState::assertNewState($this, $state);

        $this->state = $state;
    }

    /**
     * Force a state without safety checks of the domain.
     *
     * @param $state
     */
    public function forceState($state)
    {
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

    public function empty(): bool
    {
        return count($this->items) < 1;
    }

    public function items(): ItemCollection
    {
        return $this->items;
    }

    public function subtotal(): Money
    {
        return array_reduce($this->items->all(), function ($carry, Item $item) {
            return $carry->add($item->total());
        }, Cash::make(0));
    }

    public function combinedDiscounts(): array
    {
        return array_merge(
            $this->discounts(),
            $this->shippingDiscounts(),
            $this->paymentDiscounts()
        );
    }

    /**
     * Baseprice where discount will be calculated on.
     *
     * @return Money
     */
    public function discountBasePrice(): Money
    {
        return $this->subtotal();
    }

    public function discountPercentage(): Percentage
    {
        return $this->discountPercentage;
    }

    public function setDiscountPercentage(Percentage $percentage)
    {
        $this->discountPercentage = $percentage;
    }

    public function discountTotal(): Money
    {
        return $this->discountTotal;
    }

    public function addToDiscountTotal(Money $addition)
    {
        $this->discountTotal = $this->discountTotal->add($addition);
    }

    public function discounts(): array
    {
        return $this->discounts;
    }

    /**
     * Add applied discounts.
     *
     * @param $appliedDiscount
     */
    public function addDiscount(AppliedDiscount $appliedDiscount)
    {
        $this->discounts[] = $appliedDiscount;
    }

    public function removeDiscounts()
    {
        $this->discountTotal = Cash::make(0);
        $this->discountPercentage = Percentage::fromPercent(0);
        $this->discounts = [];
    }

    public function total(): Money
    {
        return $this->subtotal()
                    ->subtract($this->discountTotal())
                    ->add($this->paymentTotal())
                    ->add($this->shippingTotal());
    }

    public function taxPercentage(): Percentage
    {
        return $this->taxPercentage;
    }

    public function setTaxPercentage(Percentage $taxPercentage)
    {
        $this->taxPercentage = $taxPercentage;
    }

    public function taxTotal(): Money
    {
        return array_reduce($this->taxRates(), function ($carry, $taxRate) {
            return $carry->add($taxRate['tax']);
        }, Cash::make(0));
    }

    /**
     * Collection of used taxRates and their resp. tax amount
     * TODO: add shipment and discount tax as well.
     *
     * @return array
     */
    public function taxRates(): array
    {
        return (new SumOfTaxes())->forOrder($this);

        // Global amounts such as discountTotal, shippingTotal and PaymentTotal also have an inclusive tax.
        // This tax is the default one for this order
        // TODO: determine the default tax!!!! Default tax is the one set by the admin
        // e.g. new OrderTaxRate($defaultTaxRate,$this);
    }
}
