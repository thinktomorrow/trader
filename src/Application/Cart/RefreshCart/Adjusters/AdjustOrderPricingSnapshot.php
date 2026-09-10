<?php

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Application\VatRate\Allocator\VatAllocator;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\Snapshot\OrderPricingSnapshot;

class AdjustOrderPricingSnapshot implements Adjuster
{
    public function __construct(private VatAllocator $vatAllocator) {}

    public function adjust(Order $order): void
    {
        $order->applyPricingSnapshot($this->calculate($order));
    }

    public function calculate(Order $order): OrderPricingSnapshot
    {
        $vatAllocatedTotalPrices = $this->vatAllocator->allocateOrder($order);

        return OrderPricingSnapshot::fromVatAllocation(
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
    }
}
