<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Order\Pricing;

use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustOrderPricingSnapshot;
use Thinktomorrow\Trader\Application\Order\Pricing\Exceptions\CannotRecalculateFrozenOrderWithoutPricingSnapshot;
use Thinktomorrow\Trader\Application\Order\Pricing\Exceptions\FrozenOrderPricingWouldChange;
use Thinktomorrow\Trader\Application\Order\Pricing\OrderPricingApplication;
use Thinktomorrow\Trader\Application\VatRate\Allocator\ProRateAllocator;
use Thinktomorrow\Trader\Application\VatRate\Allocator\VatAllocator;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedLine;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Snapshot\OrderPricingSnapshot;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;

final class OrderPricingApplicationTest extends TestCase
{
    private AdjustOrderPricingSnapshot $adjuster;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adjuster = new AdjustOrderPricingSnapshot(new VatAllocator(new ProRateAllocator));
    }

    public function test_it_recalculates_and_persists_a_mutable_order(): void
    {
        $order = $this->order();
        $repository = $this->repositoryReturning($order);
        $repository->expects($this->once())->method('save')->with($order);

        $result = (new OrderPricingApplication($repository, $this->adjuster))
            ->recalculate($order->orderId);

        $this->assertTrue($result->persisted);
        $this->assertNull($result->previous);
        $this->assertSame($result->recalculated, $order->findPricingSnapshot());
    }

    public function test_a_dry_run_does_not_mutate_or_persist_the_order(): void
    {
        $order = $this->order();
        $repository = $this->repositoryReturning($order);
        $repository->expects($this->never())->method('save');

        $result = (new OrderPricingApplication($repository, $this->adjuster))
            ->recalculate($order->orderId, false);

        $this->assertFalse($result->persisted);
        $this->assertFalse($order->hasPricingSnapshot());
        $this->assertSame('0', $result->recalculated->getTotalIncl()->getAmount());
    }

    public function test_it_recalculates_a_frozen_order_when_the_customer_total_is_unchanged(): void
    {
        $order = $this->frozenOrder();
        $repository = $this->repositoryReturning($order);
        $repository->expects($this->once())->method('save')->with($order);

        $result = (new OrderPricingApplication($repository, $this->adjuster))
            ->recalculate($order->orderId);

        $this->assertTrue($result->persisted);
        $this->assertSame('0', $result->recalculated->getTotalIncl()->getAmount());
    }

    public function test_it_rejects_a_changed_customer_total_for_a_frozen_order(): void
    {
        $order = $this->frozenOrder();
        $repository = $this->repositoryReturning($order);
        $repository->expects($this->never())->method('save');
        $adjuster = $this->createMock(AdjustOrderPricingSnapshot::class);
        $adjuster->method('calculate')->willReturn($this->snapshotWithTotal(1));

        $this->expectException(FrozenOrderPricingWouldChange::class);

        (new OrderPricingApplication($repository, $adjuster))->recalculate($order->orderId);
    }

    public function test_it_rejects_changed_frozen_pricing_even_when_total_including_vat_is_unchanged(): void
    {
        $order = $this->frozenOrder();
        $repository = $this->repositoryReturning($order);
        $repository->expects($this->never())->method('save');
        $adjuster = $this->createMock(AdjustOrderPricingSnapshot::class);
        $adjuster->method('calculate')->willReturn($this->snapshotWithChangedExcludingTotal());

        $this->expectException(FrozenOrderPricingWouldChange::class);
        $this->expectExceptionMessage('subtotal_excl');

        (new OrderPricingApplication($repository, $adjuster))->recalculate($order->orderId);
    }

    public function test_it_rejects_a_frozen_order_without_a_readable_pricing_snapshot(): void
    {
        $order = $this->frozenOrder();
        $state = $order->getMappedData();
        $state['order_state'] = DefaultOrderState::confirmed;
        $state['vat_lines'] = 'invalid';
        $order = Order::fromMappedData($state);
        $repository = $this->repositoryReturning($order);
        $repository->expects($this->never())->method('save');

        $this->expectException(CannotRecalculateFrozenOrderWithoutPricingSnapshot::class);

        (new OrderPricingApplication($repository, $this->adjuster))->recalculate($order->orderId);
    }

    private function order(): Order
    {
        return Order::create(
            OrderId::fromString('order-pricing-test'),
            OrderReference::fromString('ORDER-PRICING-TEST'),
            DefaultOrderState::cart_pending,
        );
    }

    private function frozenOrder(): Order
    {
        $order = $this->order();
        $this->adjuster->adjust($order);
        $state = $order->getMappedData();
        $state['order_state'] = DefaultOrderState::confirmed;

        return Order::fromMappedData($state);
    }

    private function snapshotWithTotal(int $amount): OrderPricingSnapshot
    {
        $money = Money::EUR($amount);
        $zero = Money::EUR(0);

        return OrderPricingSnapshot::fromVatAllocation(
            vatLines: [new VatAllocatedLine($money, $zero, VatPercentage::zero())],
            subtotalExcl: $money,
            subtotalIncl: $money,
            shippingExcl: $zero,
            shippingIncl: $zero,
            paymentExcl: $zero,
            paymentIncl: $zero,
            discountExcl: $zero,
            discountIncl: $zero,
            totalExcl: $money,
            totalVat: $zero,
            totalIncl: $money,
            pricingFingerprint: 'changed',
        );
    }

    private function snapshotWithChangedExcludingTotal(): OrderPricingSnapshot
    {
        $excluding = Money::EUR(-1);
        $vat = Money::EUR(1);
        $including = Money::EUR(0);

        return OrderPricingSnapshot::fromVatAllocation(
            vatLines: [new VatAllocatedLine($excluding, $vat, VatPercentage::zero())],
            subtotalExcl: $excluding,
            subtotalIncl: $including,
            shippingExcl: Money::EUR(0),
            shippingIncl: Money::EUR(0),
            paymentExcl: Money::EUR(0),
            paymentIncl: Money::EUR(0),
            discountExcl: Money::EUR(0),
            discountIncl: Money::EUR(0),
            totalExcl: $excluding,
            totalVat: $vat,
            totalIncl: $including,
            pricingFingerprint: 'changed',
        );
    }

    /** @return OrderRepository&MockObject */
    private function repositoryReturning(Order $order): OrderRepository
    {
        $repository = $this->createMock(OrderRepository::class);
        $repository->method('find')->with($order->orderId)->willReturn($order);

        return $repository;
    }
}
