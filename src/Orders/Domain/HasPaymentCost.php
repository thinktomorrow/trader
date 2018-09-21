<?php

namespace Thinktomorrow\Trader\Orders\Domain;

use Money\Money;

trait HasPaymentCost
{
    /** @var PaymentCost */
    private $paymentCost;

    public function paymentCost(): PaymentCost
    {
        return $this->paymentCost;
    }

    public function paymentTotal(): Money
    {
        return $this->paymentCost->total();
    }

    /**
     * @deprecated use setPaymentSubtotal() instead
     *
     * @param Money $paymentTotal
     *
     * @return $this
     */
    public function setPaymentTotal(Money $paymentTotal)
    {
        return $this->setPaymentSubtotal($paymentTotal);
    }

    public function paymentSubtotal(): Money
    {
        return $this->paymentCost->subtotal();
    }

    public function setPaymentSubtotal(Money $subtotal)
    {
        $this->paymentCost->setSubtotal($subtotal);

        return $this;
    }

    public function paymentDiscountTotal(): Money
    {
        // not including payment and payment discounts
        return $this->paymentCost->discountTotal();
    }

    public function paymentDiscounts(): array
    {
        // not including payment and payment discounts
        return $this->paymentCost->discounts();
    }

    public function removePaymentDiscounts()
    {
        $this->paymentCost->removeDiscounts();
    }
}
