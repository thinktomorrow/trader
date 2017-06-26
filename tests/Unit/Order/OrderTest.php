<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Thinktomorrow\Trader\Order\Domain\ItemCollection;
use Thinktomorrow\Trader\Order\Domain\Order;

class OrderTest extends UnitTestCase
{
    /** @test */
    function it_starts_with_empty_itemcollection()
    {
        $cart = new Order();

        $this->assertInstanceOf(ItemCollection::class, $cart->items());
        $this->assertCount(0,$cart->items());
    }
}