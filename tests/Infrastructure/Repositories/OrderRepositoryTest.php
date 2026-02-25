<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\ShopperId;
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
}
