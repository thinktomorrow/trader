<?php

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Application\VatRate\Allocator\VatAllocator;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderVatSnapshot;

class AdjustOrderVatSnapshot implements Adjuster
{
    public function __construct(private VatAllocator $vatAllocator)
    {
    }

    public function adjust(Order $order): void
    {
        $vatAllocatedTotalPrices = $this->vatAllocator->allocate(
            $order,
            $order->getShippingCostExcl(),
            $order->getPaymentCostExcl(),
            $order->getDiscountTotalExcl(),
        );

        $snapShot = OrderVatSnapshot::fromVatAllocation(
            vatLines: $vatAllocatedTotalPrices->total()->getVatLines(),
            shippingIncl: $vatAllocatedTotalPrices->shipping()->getTotalIncludingVat(),
            paymentIncl: $vatAllocatedTotalPrices->payment()->getTotalIncludingVat(),
            discountIncl: $vatAllocatedTotalPrices->discounts()->getTotalIncludingVat(),
            totalVat: $vatAllocatedTotalPrices->total()->getTotalVat(),
            totalIncl: $vatAllocatedTotalPrices->total()->getTotalIncludingVat(),
            totalExcl: $order->getTotalExcl(),
        );

        $order->applyVatSnapshot($snapShot);
    }
}
