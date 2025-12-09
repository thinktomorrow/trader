<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultTotalPrice;
use Thinktomorrow\Trader\Domain\Common\Price\PriceTotal;
use Thinktomorrow\Trader\Domain\Common\Price\TotalPrice;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;

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
        $shippingItemPrice = DefaultItemPrice::fromCalculated(
            $this->getShippingCost()->getIncludingVat(),
            $this->getShippingCost()->getExcludingVat(),
            $this->getShippingCost()->getVatPercentage()
        );

        $paymentItemPrice = DefaultItemPrice::fromCalculated(
            $this->getPaymentCost()->getIncludingVat(),
            $this->getPaymentCost()->getExcludingVat(),
            $this->getPaymentCost()->getVatPercentage()
        );

        $discountTotalPrice = DefaultTotalPrice::fromCalculated(
            $this->getDiscountTotal()->getIncludingVat(),
            $this->getDiscountTotal()->getExcludingVat()
        );

        return $this->getSubTotal()
            ->subtract($discountTotalPrice)
            ->add($shippingItemPrice)
            ->add($paymentItemPrice);
    }

    //    public function getTotal(): OrderTotal
    //    {
    //        return $this->getSubTotal()
    //            ->subtract($this->getDiscountTotal())
    //            ->add($this->getShippingCost())
    //            ->add($this->getPaymentCost());
    //    }

    public function getTaxTotal(): Money
    {
        return $this->getTotal()->getVatTotal();
    }

    public function getDiscountTotal(): DiscountTotal
    {
        return $this->calculateDiscountTotal($this->getSubTotal());
    }

    public function getShippingCost(): ShippingCost
    {
        if (count($this->shippings) < 1) {
            return ShippingCost::zero();
        }

        return array_reduce($this->shippings, function (?PriceTotal $carry, Shipping $shipping) {
            return $carry === null
                ? $shipping->getShippingCost()
                : $carry->add($shipping->getShippingCost());
        }, null);
    }

    public function getPaymentCost(): PaymentCost
    {
        if (count($this->payments) < 1) {
            return PaymentCost::zero();
        }

        return array_reduce($this->payments, function (?PriceTotal $carry, Payment $payment) {
            return $carry === null
                ? $payment->getPaymentCost()
                : $carry->add($payment->getPaymentCost());
        }, null);
    }
}
