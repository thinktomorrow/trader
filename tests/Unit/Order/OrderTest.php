<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Thinktomorrow\Trader\Order\Domain\ItemCollection;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Order\Domain\OrderId;

class OrderTest extends UnitTestCase
{
    /** @test */
    function orderId_is_a_valid_identifier()
    {
        $orderId = OrderId::fromInteger(2);

        $this->assertEquals(2,$orderId->get());
    }

    /** @test */
    function it_starts_with_empty_itemcollection()
    {
        $order = $this->makeOrder();

        $this->assertInstanceOf(ItemCollection::class, $order->items());
        $this->assertCount(0,$order->items());
    }
}