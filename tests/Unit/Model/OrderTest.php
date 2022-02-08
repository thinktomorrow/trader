<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\LineNumber;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineDeleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderCreated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderUpdated;

class OrderTest extends TestCase
{
    /** @test */
    public function it_can_create_an_order_entity()
    {
        $order = Order::create(
            $orderId = OrderId::fromString('xxx'),
            $customerId = CustomerId::fromString('yyy'),
        );

        $this->assertEquals([
            'order_id' => $orderId->get(),
            'customer_id' => $customerId->get(),
        ], $order->getMappedData());

        $this->assertEquals([
            new OrderCreated($orderId),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_update_an_order_entity()
    {
        $order = $this->createdOrder();

        $order->update(CustomerId::fromString('zzz'));

        $this->assertEquals(CustomerId::fromString('zzz'), $order->getMappedData()['customer_id']);

        $this->assertEquals([
            new OrderCreated($order->orderId),
            new OrderUpdated($order->orderId),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_be_build_from_raw_data()
    {
        $order = Order::fromMappedData([
            'order_id' => 'xxx',
            'customer_id' => 'yyy',
        ]);

        $this->assertEquals(OrderId::fromString('xxx'), $order->orderId);
        $this->assertEquals(CustomerId::fromString('yyy'), $order->getMappedData()['customer_id']);
    }

    /** @test */
    public function it_can_add_a_line()
    {
        $order = $this->createdOrder();

        $order->addOrUpdateLine(
            LineNumber::fromInt(1),
            ProductId::fromString('xxx'),
            Quantity::fromInt(2),
        );

        $this->assertCount(1, $order->getChildEntities()[Line::class]);

        $this->assertEquals([
            new OrderCreated($order->orderId),
            new LineAdded(
                $order->orderId,
                LineNumber::fromInt(1),
                ProductId::fromString('xxx'),
                Quantity::fromInt(2),
            ),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_update_a_line()
    {
        $order = $this->createdOrder();

        $order->addOrUpdateLine(
            LineNumber::fromInt(1),
            ProductId::fromString('xxx'),
            Quantity::fromInt(2),
        );

        $order->addOrUpdateLine(
            LineNumber::fromInt(1),
            ProductId::fromString('yyy'),
            Quantity::fromInt(3),
        );

        $this->assertCount(1, $order->getChildEntities()[Line::class]);
        $this->assertEquals('yyy', $order->getChildEntities()[Line::class][0]->getProductId()->get());
        $this->assertEquals(3, $order->getChildEntities()[Line::class][0]->getQuantity()->asInt());

        $this->assertEquals([
            new OrderCreated($order->orderId),
            new LineAdded(
                $order->orderId,
                LineNumber::fromInt(1),
                ProductId::fromString('xxx'),
                Quantity::fromInt(2),
            ),
            new LineUpdated(
                $order->orderId,
                LineNumber::fromInt(1),
                ProductId::fromString('yyy'),
                Quantity::fromInt(3),
            ),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_delete_a_line()
    {
        $order = $this->createdOrder();

        $order->addOrUpdateLine(
            LineNumber::fromInt(1),
            ProductId::fromString('xxx'),
            Quantity::fromInt(2),
        );

        $this->assertCount(1, $order->getChildEntities()[Line::class]);

        $order->deleteLine(
            LineNumber::fromInt(1),
        );

        $this->assertCount(0, $order->getChildEntities()[Line::class]);

        $this->assertEquals([
            new OrderCreated($order->orderId),
            new LineAdded(
                $order->orderId,
                LineNumber::fromInt(1),
                ProductId::fromString('xxx'),
                Quantity::fromInt(2),
            ),
            new LineDeleted(
                $order->orderId,
                LineNumber::fromInt(1),
                ProductId::fromString('xxx'),
            ),
        ], $order->releaseEvents());
    }

    private function createdOrder(): Order
    {
        return Order::create(
            OrderId::fromString('xxx'),
            CustomerId::fromString('yyy'),
        );
    }
}
