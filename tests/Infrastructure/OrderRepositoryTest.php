<?php
declare(strict_types=1);

namespace Tests\Infrastructure;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Infrastructure\Test\ArrayOrderRepository;

final class OrderRepositoryTest extends TestCase
{
    /**
     * @test
     * @dataProvider orders
     */
    public function it_can_save_an_order(Order $order)
    {
        foreach($this->orderRepositories() as $orderRepository) {
            $orderRepository->save($order);

            $this->assertEquals($order, $orderRepository->find($order->orderId));
        }
    }

    private function orderRepositories(): \Generator
    {
        yield new ArrayOrderRepository();
    }

    public function orders(): \Generator
    {
        yield [Order::create(
            OrderId::fromString('xxx'),
            CustomerId::fromString('yyy'),
        )];
    }
}
