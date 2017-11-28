<?php

namespace Thinktomorrow\Trader\Tests;

use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\ItemId;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;

class ItemTest extends TestCase
{
    /** @test */
    public function itemId_is_a_valid_identifier()
    {
        $itemId = ItemId::fromInteger(2);

        $this->assertEquals(2, $itemId->get());
    }

    /** @test */
    public function it_can_add_to_quantity()
    {
        $item = $this->getItem(null, null, new PurchasableStub(99));

        $this->assertEquals(1, $item->quantity());
        $item->add(1);

        $this->assertEquals(2, $item->quantity());
    }

    /** @test */
    public function it_can_add_multiple_to_quantity()
    {
        $item = $this->getItem(null, null, new PurchasableStub(99));

        $this->assertEquals(1, $item->quantity());
        $item->add(5);

        $this->assertEquals(6, $item->quantity());
    }

    /** @test */
    public function it_can_remove_from_quantity()
    {
        $item = $this->getItem(null, null, new PurchasableStub(99));

        $item->add(1);
        $this->assertEquals(2, $item->quantity());

        $item->remove(1);
        $this->assertEquals(1, $item->quantity());
    }

    /** @test */
    public function it_can_remove_multiple_from_quantity()
    {
        $item = $this->getItem(null, null, new PurchasableStub(99));

        $item->add(10);
        $this->assertEquals(11, $item->quantity());

        $item->remove(8);
        $this->assertEquals(3, $item->quantity());
    }

    /** @test */
    public function it_can_remove_quantity_but_no_lower_than_zero()
    {
        $item = $this->getItem(null, null, new PurchasableStub(99));

        $item->remove(1);
        $item->remove(1);
        $this->assertEquals(0, $item->quantity());
    }
}
