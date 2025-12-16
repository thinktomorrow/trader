<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultTotalPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\TotalPrice;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\GetValidatedTotalDiscountPrice;

trait WithOrderTotals
{
    public function getSubTotal(): TotalPrice
    {
        $subtotal = DefaultTotalPrice::zero();

        foreach ($this->lines as $line) {
            $subtotal = $subtotal->add($line->getTotal());
        }

        return $subtotal;
    }

    public function getTotal(): TotalPrice
    {
        return $this->getSubTotal()
            ->subtract($this->getTotalDiscountPrice())
            ->add($this->getShippingCost())
            ->add($this->getPaymentCost());
    }

    public function getTaxTotal(): Money
    {
        // TODO: vat Allocator implementation
        // exacte VAT percentages
        //pro-rata verdeling
        //afrondingsregels
        //grouping per VAT rate
        //invoice-level VAT logica indien nodig

        return Money::zero();

//        return $this->getTotal()->getVatTotal();
    }

    /**
     * This is the discount total that is applied on the entire order (not per item).
     * Note that this is not a sum of all item discounts, but specifically the
     * order discounts total as calculated based on the subtotal.
     */
    public function getTotalDiscountPrice(): DiscountPrice
    {
        return GetValidatedTotalDiscountPrice::get($this->getSubTotal(), $this);
    }

    public function getShippingCost(): TotalPrice
    {
        $shippingCost = DefaultTotalPrice::zero();

        foreach ($this->shippings as $shipping) {
            $shippingCost = $shippingCost->add($shipping->getShippingCost());
        }

        return $shippingCost;
    }

    public function getPaymentCost(): TotalPrice
    {
        $paymentCost = DefaultTotalPrice::zero();

        foreach ($this->payments as $payment) {
            $paymentCost = $paymentCost->add($payment->getPaymentCost());
        }

        return $paymentCost;
    }
}
