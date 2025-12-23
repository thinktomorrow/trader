<?php

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Money\Money;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Application\VatRate\Allocator\VatAllocator;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

class AdjustOrderTotals implements Adjuster
{
    public function __construct(private VatAllocator $vatAllocator)
    {
    }

    public function adjust(Order $order): void
    {
        $vatAllocatedTotalPrices = $this->vatAllocator->allocate(
            $order,
            $this->getShippingTotalExcludingVat($order),
            $this->getPaymentTotalExcludingVat($order),
            $this->getDiscountTotalExcludingVat($order),
        );

        $order->applySubtotalTotals(
            $vatAllocatedTotalPrices->items(),
        );O

        $order->applyServiceTotals(
            $vatAllocatedTotalPrices->shipping(),
            $vatAllocatedTotalPrices->payment(),
        );

        $order->applyDiscountTotals(
            $vatAllocatedTotalPrices->discounts(),
        );

        $order->applyTotals(
            $vatAllocatedTotalPrices->total()
        );
    }

    private function getShippingTotalExcludingVat(Order $order): Money
    {
        $total = Money::EUR(0);

        foreach ($order->getShippings() as $shipping) {
            $total = $total->add($shipping->getShippingCost()->getExcludingVat());
        }

        return $total;
    }

    private function getPaymentTotalExcludingVat(Order $order): Money
    {
        $total = Money::EUR(0);

        foreach ($order->getPayments() as $payment) {
            $total = $total->add($payment->getPaymentCost()->getExcludingVat());
        }

        return $total;
    }

    private function getDiscountTotalExcludingVat(Order $order): Money
    {
        $total = Money::EUR(0);

        foreach ($order->getDiscounts() as $discount) {
            $total = $total->add($discount->getDiscountAmount()->getExcludingVat());
        }

        return $total;
    }
}
