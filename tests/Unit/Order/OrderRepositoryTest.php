<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Orders\Domain\Exceptions\OrderNotFound;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Orders\Domain\OrderReference;
use Thinktomorrow\Trader\Orders\Domain\OrderState;
use Thinktomorrow\Trader\Orders\Ports\Persistence\InMemoryOrderRepository;
use Thinktomorrow\Trader\Tests\Unit\Stubs\PurchasableStub;

class OrderRepositoryTest extends UnitTestCase
{
    /** @test */
    public function it_can_find_an_order()
    {
        $order = $this->makeOrder(0, 3);
        $repo = new InMemoryOrderRepository();

        $repo->add($order);

        $this->assertEquals($order, $repo->find(OrderId::fromInteger(3)));
    }

    /** @test */
    public function it_can_find_or_create_an_order()
    {
        $order = $this->makeOrder(0, 3);

        $repo = new InMemoryOrderRepository();
        $repo->add($order);

        $this->assertEquals($order, $repo->findOrCreate(OrderId::fromInteger(3)));
        $this->assertNotEquals($order, $repo->findOrCreate(OrderId::fromInteger(4)));
    }

    /** @test */
    public function it_can_get_next_identity()
    {
        $repo = new InMemoryOrderRepository();

        $id = $repo->nextIdentity();

        $this->assertInstanceOf(OrderId::class, $id);

        // Check valid UUID
        $UUIDv4 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
        $this->assertTrue((bool) preg_match($UUIDv4, $id->get()));
    }

    /** @test */
    public function it_throws_exception_if_order_does_not_exist()
    {
        $this->expectException(OrderNotFound::class, 'No order found');

        $repo = new InMemoryOrderRepository();
        $repo->find(OrderId::fromInteger(9));
    }

}
