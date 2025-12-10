<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultTotalPrice;
use Thinktomorrow\Trader\Domain\Common\Price\ItemDiscount;
use Thinktomorrow\Trader\Domain\Common\Price\TotalPrice;

trait HasTotals
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
        $discountTotalPrice = DefaultTotalPrice::fromCalculated(
            $this->getDiscountTotal()->getIncludingVat(),
            $this->getDiscountTotal()->getExcludingVat()
        );

        return $this->getSubTotal()
            ->subtract($this->getDiscountTotal())
            ->add($this->getShippingCost())
            ->add($this->getPaymentCost());
    }

    public function getTaxTotal(): Money
    {
        return $this->getTotal()->getVatTotal();
    }

    /**
     * This is the discount total that is applied on the entire order (not per item).
     * Note that this is not a sum of all item discounts, but specifically the
     * order discounts total as calculated based on the subtotal.
     *
     * @return ItemDiscount
     */
    public function getDiscountTotal(): ItemDiscount
    {
        return $this->calculateItemDiscount($this->getSubTotal());
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
            $paymentCost = $paymentCost->add($payment->getShippingCost());
        }

        return $paymentCost;
    }
}
