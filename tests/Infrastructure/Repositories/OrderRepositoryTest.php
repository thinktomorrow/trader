<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;

final class OrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider orders
     */
    public function it_can_save_and_find_an_order(Order $order)
    {
        foreach ($this->orderRepositories() as $orderRepository) {
            $orderRepository->save($order);
            $order->releaseEvents();

            $this->assertEquals($order, $orderRepository->find($order->orderId));
        }
    }

    /**
     * @test
     * @dataProvider orders
     */
    public function it_can_delete_an_order(Order $order)
    {
        $ordersNotFound = 0;

        foreach ($this->orderRepositories() as $orderRepository) {
            $orderRepository->save($order);
            $orderRepository->delete($order->orderId);

            try {
                $orderRepository->find($order->orderId);
            } catch (CouldNotFindOrder $e) {
                $ordersNotFound++;
            }
        }

        $this->assertEquals(count(iterator_to_array($this->orderRepositories())), $ordersNotFound);
    }

    /** @test */
    public function it_can_generate_a_next_reference()
    {
        foreach ($this->orderRepositories() as $orderRepository) {
            $this->assertInstanceOf(OrderId::class, $orderRepository->nextReference());
        }
    }

    /** @test */
    public function it_can_generate_a_next_shipping_reference()
    {
        foreach ($this->orderRepositories() as $orderRepository) {
            $this->assertInstanceOf(ShippingId::class, $orderRepository->nextShippingReference());
        }
    }

    private function orderRepositories(): \Generator
    {
        yield new InMemoryOrderRepository();
        yield new MysqlOrderRepository();
    }

    public function orders(): \Generator
    {
        yield [$this->createdOrder()];

        $orderWithDiscount = $this->createdOrder();
        $orderWithDiscount->addDiscount($this->createOrderDiscount($orderWithDiscount->orderId, ['discount_id' => 'def', 'promo_discount_id' => 'ghi']));
        yield [$orderWithDiscount];

        $orderWithLineDiscount = $this->createdOrder();
        $orderWithLineDiscount->getLines()[0]->addDiscount($this->createOrderDiscount($orderWithLineDiscount->orderId, [
            'discount_id' => 'def',
            'promo_discount_id' => 'ghi',
            'discountable_id' => $orderWithLineDiscount->getLines()[0]->lineId->get(),
            'discountable_type' => DiscountableType::line->value,
        ]));
        yield [$orderWithLineDiscount];

        $orderWithShippingDiscount = $this->createdOrder();
        $orderWithShippingDiscount->getShippings()[0]->addDiscount($this->createOrderDiscount($orderWithShippingDiscount->orderId, [
            'discount_id' => 'def',
            'promo_discount_id' => 'ghi',
            'discountable_id' => $orderWithShippingDiscount->getShippings()[0]->shippingId->get(),
            'discountable_type' => DiscountableType::shipping->value,
        ]));
        yield [$orderWithShippingDiscount];

        yield [
            Order::create(
                OrderId::fromString('xxx'),
                OrderReference::fromString('xx-ref')
            ),
        ];
    }
}
