<?php

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Application\VatRate\Allocator\VatAllocator;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderVatSnapshot;

class AdjustOrderVatSnapshot implements Adjuster
{
    public function __construct(private VatAllocator $vatAllocator) {}

    public function adjust(Order $order): void
    {
        $vatAllocatedTotalPrices = $this->vatAllocator->allocateOrder($order);

        $snapShot = OrderVatSnapshot::fromVatAllocation(
            vatLines: $vatAllocatedTotalPrices->total()->getVatLines(),
            subtotalExcl: $vatAllocatedTotalPrices->items()->getTotalExcludingVat(),
            subtotalIncl: $vatAllocatedTotalPrices->items()->getTotalIncludingVat(),
            shippingExcl: $vatAllocatedTotalPrices->shipping()->getTotalExcludingVat(),
            shippingIncl: $vatAllocatedTotalPrices->shipping()->getTotalIncludingVat(),
            paymentExcl: $vatAllocatedTotalPrices->payment()->getTotalExcludingVat(),
            paymentIncl: $vatAllocatedTotalPrices->payment()->getTotalIncludingVat(),
            discountExcl: $vatAllocatedTotalPrices->discounts()->getTotalExcludingVat(),
            discountIncl: $vatAllocatedTotalPrices->discounts()->getTotalIncludingVat(),
            totalExcl: $vatAllocatedTotalPrices->total()->getTotalExcludingVat(),
            totalVat: $vatAllocatedTotalPrices->total()->getTotalVat(),
            totalIncl: $vatAllocatedTotalPrices->total()->getTotalIncludingVat(),
            pricingFingerprint: $order->getPricingFingerprint(),
        );

        $order->applyVatSnapshot($snapShot);
    }
}
