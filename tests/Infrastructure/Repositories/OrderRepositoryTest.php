<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Support\Facades\DB;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Order\State\Order\AbandonOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\ShopperId;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Testing\Order\OrderContext;

final class OrderRepositoryTest extends TestCase
{
    public function test_it_can_save_and_find_an_order()
    {
        /** @var OrderContext $orderContext */
        foreach (OrderContext::drivers() as $orderContext) {
            $order = $orderContext->dontPersist()->createOrder();

            $repository = $orderContext->repos()->orderRepository();

            $repository->save($order);

            $order->releaseEvents();

            $this->assertEquals($order, $repository->find($order->orderId));
        }
    }

    public function test_it_can_delete_an_order()
    {
        $ordersNotFound = 0;

        /** @var OrderContext $orderContext */
        foreach (OrderContext::drivers() as $orderContext) {
            $order = $orderContext->createOrder();

            $repository = $orderContext->repos()->orderRepository();

            $repository->delete($order->orderId);

            try {
                $repository->find($order->orderId);
            } catch (CouldNotFindOrder $e) {
                $ordersNotFound++;
            }
        }

        $this->assertCount($ordersNotFound, OrderContext::drivers());
    }

    public function test_it_can_generate_a_next_reference()
    {
        /** @var OrderContext $orderContext */
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->orderRepository();

            $this->assertInstanceOf(OrderId::class, $repository->nextReference());
        }
    }

    public function test_it_can_generate_a_next_external_reference()
    {
        /** @var OrderContext $orderContext */
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->orderRepository();

            $this->assertInstanceOf(OrderReference::class, $repository->nextExternalReference());
        }
    }

    public function test_it_can_generate_a_next_shipping_and_shopper_reference()
    {
        /** @var OrderContext $orderContext */
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->orderRepository();

            $this->assertInstanceOf(ShippingId::class, $repository->nextShippingReference());
            $this->assertInstanceOf(ShopperId::class, $repository->nextShopperReference());
        }
    }

    public function test_it_repairs_stale_vat_snapshot_when_abandoning_order(): void
    {
        foreach ([OrderContext::mysql(), OrderContext::laravel()] as $orderContext) {
            $order = $orderContext->createDefaultOrder('vatfix-'.$orderContext->driverName);

            DB::table('trader_orders')
                ->where('order_id', $order->orderId->get())
                ->update([
                    'total_incl' => 0,
                    'total_vat' => 0,
                    'discount_incl' => 0,
                    'shipping_cost_incl' => 0,
                    'payment_cost_incl' => 0,
                    'vat_lines' => json_encode([]),
                ]);

            $orderContext->apps()->orderStateApplication()->abandonOrder(new AbandonOrder($order->orderId->get()));

            $savedOrder = $orderContext->findOrder($order->orderId);

            $this->assertEquals(DefaultOrderState::cart_abandoned, $savedOrder->getOrderState());
            $this->assertTrue($savedOrder->hasUpToDateVatSnapshot());
        }
    }
}
