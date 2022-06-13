<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCartRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCartRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlOrderRepository;

final class CartRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider orders
     */
    public function it_can_find_a_cart(Order $order)
    {
        foreach ($this->orderRepositories() as $i => $orderRepository) {
            $orderRepository->save($order);
            $order->releaseEvents();

            $cartRepository = iterator_to_array($this->cartRepositories())[$i];

            trap($cartRepository->findCart($order->orderId));
        }
    }

    private function orderRepositories(): \Generator
    {
        yield new InMemoryOrderRepository();
        yield new MysqlOrderRepository();
    }

    private function cartRepositories(): \Generator
    {
        yield new InMemoryCartRepository();
        yield new MysqlCartRepository();
    }

    public function orders(): \Generator
    {
        yield [$this->createdOrder()];

        yield [
            Order::create(
                OrderId::fromString('xxx'),
            ),
        ];
    }
}
