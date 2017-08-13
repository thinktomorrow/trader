<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Domain\ItemId;
use Thinktomorrow\Trader\Tests\Unit\Stubs\PurchasableStub;

class ItemTest extends UnitTestCase
{
    /** @test */
    function itemId_is_a_valid_identifier()
    {
        $itemId = ItemId::fromInteger(2);

        $this->assertEquals(2,$itemId->get());
    }

    /** @test */
    function it_can_add_to_quantity()
    {
        $item = Item::fromPurchasable(new PurchasableStub(99));

        $this->assertEquals(1, $item->quantity());
        $item->add(1);

        $this->assertEquals(2, $item->quantity());
    }

    /** @test */
    function it_can_add_multiple_to_quantity()
    {
        $item = Item::fromPurchasable(new PurchasableStub(99));

        $this->assertEquals(1, $item->quantity());
        $item->add(5);

        $this->assertEquals(6, $item->quantity());
    }

    /** @test */
    function it_can_remove_from_quantity()
    {
        $item = Item::fromPurchasable(new PurchasableStub(99));

        $item->add(1);
        $this->assertEquals(2, $item->quantity());

        $item->remove(1);
        $this->assertEquals(1, $item->quantity());
    }

    /** @test */
    function it_can_remove_multiple_from_quantity()
    {
        $item = Item::fromPurchasable(new PurchasableStub(99));

        $item->add(10);
        $this->assertEquals(11, $item->quantity());

        $item->remove(8);
        $this->assertEquals(3, $item->quantity());
    }

    /** @test */
    function it_can_remove_quantity_but_no_lower_than_zero()
    {
        $item = Item::fromPurchasable(new PurchasableStub(99));

        $item->remove(1);
        $item->remove(1);
        $this->assertEquals(0, $item->quantity());
    }
}