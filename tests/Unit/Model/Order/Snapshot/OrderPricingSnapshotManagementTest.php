<?php

declare(strict_types=1);

namespace Tests\Unit\Model\Order\Snapshot;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\Snapshot\OrderPricingSnapshot;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;

final class OrderPricingSnapshotManagementTest extends TestCase
{
    public function test_it_applies_validates_and_invalidates_a_pricing_snapshot(): void
    {
        $order = Order::create(
            OrderId::fromString('order-aaa'),
            OrderReference::fromString('ORDER-AAA'),
            DefaultOrderState::cart_pending,
        );
        $zero = Money::EUR(0);
        $snapshot = OrderPricingSnapshot::fromVatAllocation(
            vatLines: [],
            subtotalExcl: $zero,
            subtotalIncl: $zero,
            shippingExcl: $zero,
            shippingIncl: $zero,
            paymentExcl: $zero,
            paymentIncl: $zero,
            discountExcl: $zero,
            discountIncl: $zero,
            totalExcl: $zero,
            totalVat: $zero,
            totalIncl: $zero,
            pricingFingerprint: $order->getPricingFingerprint(),
        );

        $order->applyPricingSnapshot($snapshot);

        $this->assertTrue($order->hasPricingSnapshot());
        $this->assertTrue($order->hasUpToDatePricingSnapshot());
        $this->assertEquals($zero, $order->getTotalIncl());

        $order->invalidatePricingSnapshot();

        $this->assertFalse($order->hasPricingSnapshot());
    }
}
