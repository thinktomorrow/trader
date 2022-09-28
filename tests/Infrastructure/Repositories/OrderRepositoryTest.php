<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Domain\Model\Order\OrderEvent\OrderEvent;
use Thinktomorrow\Trader\Domain\Model\Order\OrderEvent\OrderEventId;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

final class OrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_save_and_find_an_order()
    {
        foreach ($this->orders() as $order) {
            foreach ($this->orderRepositories() as $orderRepository) {
                $orderRepository->save($order);
                $order->releaseEvents();

                $this->assertEquals($order, $orderRepository->find($order->orderId));

                // Cleanup
                $orderRepository->delete($order->orderId);
            }
        }
    }

    /**
     * @test
     */
    public function it_can_delete_an_order()
    {
        foreach ($this->orders() as $order) {
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
    }

    /** @test */
    public function it_can_generate_a_next_reference()
    {
        foreach ($this->orderRepositories() as $orderRepository) {
            $this->assertInstanceOf(OrderId::class, $orderRepository->nextReference());
        }
    }

    /** @test */
    public function it_can_generate_a_next_external_reference()
    {
        foreach ($this->orderRepositories() as $orderRepository) {
            $this->assertInstanceOf(OrderReference::class, $orderRepository->nextExternalReference());
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
        yield (new TestContainer())->get(MysqlOrderRepository::class);
    }

    public function orders(): \Generator
    {
        yield $this->createDefaultOrder();

        $orderWithDiscount = $this->createDefaultOrder();
        $orderWithDiscount->addDiscount($this->createOrderDiscount(['discount_id' => 'def', 'promo_discount_id' => 'ghi'], $orderWithDiscount->getMappedData()));
        yield $orderWithDiscount;

        $orderWithLineDiscount = $this->createDefaultOrder();
        $orderWithLineDiscount->getLines()[0]->addDiscount($this->createOrderDiscount([
            'discount_id' => 'def',
            'promo_discount_id' => 'ghi',
            'discountable_id' => $orderWithLineDiscount->getLines()[0]->lineId->get(),
            'discountable_type' => DiscountableType::line->value,
        ], $orderWithLineDiscount->getMappedData()));
        yield $orderWithLineDiscount;

        $orderWithShippingDiscount = $this->createDefaultOrder();
        $orderWithShippingDiscount->getShippings()[0]->addDiscount($this->createOrderDiscount([
            'discount_id' => 'def',
            'promo_discount_id' => 'ghi',
            'discountable_id' => $orderWithShippingDiscount->getShippings()[0]->shippingId->get(),
            'discountable_type' => DiscountableType::shipping->value,
        ], $orderWithShippingDiscount->getMappedData()));
        yield $orderWithShippingDiscount;

        yield Order::create(
            OrderId::fromString('xxx'),
            OrderReference::fromString('xx-ref')
        );

        $orderWithLogEntries = $this->createDefaultOrder();
        $orderWithLogEntries->addLogEntry(OrderEvent::create(OrderEventId::fromString('def'), 'yyy', new \DateTime('2022-03-01 19:19:00'), ['foo' => 'baz']));
        yield $orderWithLogEntries;
    }
}
