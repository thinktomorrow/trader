<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Domain\ItemCollection;
use Thinktomorrow\Trader\Order\Domain\ItemId;
use Thinktomorrow\Trader\Tests\Unit\Stubs\PurchasableStub;

class ItemCollectionTest extends UnitTestCase
{
    /** @test */
    function it_starts_with_empty_items()
    {
        $collection = new ItemCollection();

        $this->assertTrue($collection->isEmpty());
    }

    /** @test */
    function it_can_start_with_array_of_items()
    {
        $collection = new ItemCollection(
            Item::fromPurchasable(new PurchasableStub(1)),
            Item::fromPurchasable(new PurchasableStub(2))
        );

        $this->assertEquals(2,$collection->size());
    }

    /** @test */
    function same_items_are_added_up()
    {
        $collection = new ItemCollection(
            Item::fromPurchasable(new PurchasableStub(2)),
            Item::fromPurchasable(new PurchasableStub(2))
        );

        $this->assertEquals(1,$collection->size());
        $this->assertEquals(2,$collection->find(ItemId::fromInteger(2))->quantity());
    }

    /** @test */
    function it_can_add_an_item()
    {
        $itemCollection = new ItemCollection();
        $item = Item::fromPurchasable(new PurchasableStub(99));

        $itemCollection->add($item);

        $this->assertFalse($itemCollection->isEmpty());
        $this->assertEquals(1,$itemCollection->size());
    }

    /** @test */
    function it_can_add_an_item_with_quantity()
    {
        $itemCollection = new ItemCollection();
        $item = Item::fromPurchasable(new PurchasableStub(99));
        $itemCollection->add($item,3);

        $this->assertEquals(1,$itemCollection->size());
        $this->assertEquals(3,$item->quantity());
    }

    /** @test */
    function it_can_find_an_item_by_id()
    {
        $itemCollection = new ItemCollection();
        $item = Item::fromPurchasable(new PurchasableStub(99));

        $itemCollection->add($item);

        $this->assertSame($item, $itemCollection->find($item->id()));
    }

    /** @test */
    function adding_same_item_quantifies_same_item()
    {
        $itemCollection = new ItemCollection();
        $item = Item::fromPurchasable(new PurchasableStub(99));

        $itemCollection->add($item);
        $itemCollection->add($item);

        $this->assertSame($item, $itemCollection->find($item->id()));
        $this->assertEquals(2,$item->quantity());
    }
}