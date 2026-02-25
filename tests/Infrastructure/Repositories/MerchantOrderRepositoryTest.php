<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrder;
use Thinktomorrow\Trader\Testing\Order\OrderContext;

class MerchantOrderRepositoryTest extends TestCase
{
    public function test_it_can_find_a_merchantorder()
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $order = $orderContext->createDefaultOrder();

            $repository = $orderContext->repos()->merchantOrderRepository();

            $merchantOrder = $repository->findMerchantOrder($order->orderId);

            $this->assertInstanceOf(MerchantOrder::class, $merchantOrder);
        }
    }

    public function test_it_can_find_a_merchantorder_by_reference()
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $order = $orderContext->createDefaultOrder();

            $repository = $orderContext->repos()->merchantOrderRepository();

            $merchantOrder = $repository->findMerchantOrderByReference($order->orderReference);

            $this->assertInstanceOf(MerchantOrder::class, $merchantOrder);
        }
    }
}
