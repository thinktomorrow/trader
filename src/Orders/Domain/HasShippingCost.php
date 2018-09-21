<?php

namespace Thinktomorrow\Trader\Orders\Domain;

use Money\Money;

trait HasShippingCost
{
    /** @var ShippingCost */
    private $shippingCost;

    public function shippingCost(): ShippingCost
    {
        return $this->shippingCost;
    }

    public function shippingTotal(): Money
    {
        return $this->shippingCost->total();
    }

    /**
     * @deprecated use setShippingSubtotal() instead
     *
     * @param Money $shippingTotal
     *
     * @return $this
     */
    public function setShippingTotal(Money $shippingTotal)
    {
        return $this->setShippingSubtotal($shippingTotal);
    }

    public function shippingSubtotal(): Money
    {
        return $this->shippingCost->subtotal();
    }

    public function setShippingSubtotal(Money $subtotal)
    {
        $this->shippingCost->setSubtotal($subtotal);

        return $this;
    }

    public function shippingDiscountTotal(): Money
    {
        // not including shipping and payment discounts
        return $this->shippingCost->discountTotal();
    }

    public function shippingDiscounts(): array
    {
        // not including shipping and payment discounts
        return $this->shippingCost->discounts();
    }

    public function removeShippingDiscounts()
    {
        $this->shippingCost->removeDiscounts();
    }
}
