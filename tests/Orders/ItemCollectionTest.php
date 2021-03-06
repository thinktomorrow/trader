<?php

namespace Thinktomorrow\Trader\Tests;

use Thinktomorrow\Trader\Orders\Domain\ItemCollection;
use Thinktomorrow\Trader\Orders\Domain\PurchasableId;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;

class ItemCollectionTest extends TestCase
{
    /** @test */
    public function it_starts_with_empty_items()
    {
        $collection = new ItemCollection();

        $this->assertTrue($collection->isEmpty());
    }

    /** @test */
    public function it_can_start_with_array_of_items()
    {
        $collection = new ItemCollection(
            $this->getItem(null, null, new PurchasableStub(1)),
            $this->getItem(null, null, new PurchasableStub(2))
        );

        $this->assertEquals(2, $collection->size());
    }

    /** @test */
    public function same_items_are_added_up()
    {
        $collection = new ItemCollection(
            $this->getItem(null, null, new PurchasableStub(2)),
            $this->getItem(null, null, new PurchasableStub(2))
        );

        $this->assertEquals(1, $collection->size());
        $this->assertEquals(2, $collection->find(PurchasableId::fromInteger(2))->quantity());
    }

    /** @test */
    public function it_can_add_an_item()
    {
        $itemCollection = new ItemCollection();
        $item = $this->getItem(null, null, new PurchasableStub(99));

        $itemCollection->add($item);

        $this->assertFalse($itemCollection->isEmpty());
        $this->assertEquals(1, $itemCollection->size());
    }

    /** @test */
    public function it_can_add_an_item_with_quantity()
    {
        $itemCollection = new ItemCollection();
        $item = $this->getItem(null, null, new PurchasableStub(99));
        $itemCollection->add($item, 3);

        $this->assertEquals(1, $itemCollection->size());
        $this->assertEquals(3, $item->quantity());
    }

    /** @test */
    public function it_can_replace_an_item_quantity()
    {
        $itemCollection = new ItemCollection();
        $item = $this->getItem(null, null, new PurchasableStub(99));
        $itemCollection->add($item, 3);

        $itemCollection->replace($item->purchasableId(), 5);

        $this->assertEquals(1, $itemCollection->size());
        $this->assertEquals(5, $item->quantity());
        $this->assertEquals(5, $itemCollection->all()[99]->quantity());
    }

    /** @test */
    public function item_below_zero_removes_item()
    {
        $itemCollection = new ItemCollection();
        $item = $this->getItem(null, null, new PurchasableStub(99));
        $itemCollection->add($item, 3);

        $itemCollection->replace($item->purchasableId(), -2);

        $this->assertEquals(0, $itemCollection->size());
        $this->assertEquals(0, $item->quantity());
    }

    /** @test */
    public function it_cannot_replace_a_non_existing_item()
    {
        $this->expectException(\InvalidArgumentException::class);

        $itemCollection = new ItemCollection();
        $item = $this->getItem(null, null, new PurchasableStub(99));

        $itemCollection->replace($item->purchasableId(), 5);
    }

    /** @test */
    public function it_can_find_an_item_by_id()
    {
        $itemCollection = new ItemCollection();
        $item = $this->getItem(null, null, new PurchasableStub(99));

        $itemCollection->add($item);

        $this->assertSame($item, $itemCollection->find($item->purchasableId()));
    }

    /** @test */
    public function adding_same_item_quantifies_same_item()
    {
        $itemCollection = new ItemCollection();
        $item = $this->getItem(null, null, new PurchasableStub(99));

        $itemCollection->add($item);
        $itemCollection->add($item);

        $this->assertSame($item, $itemCollection->find($item->purchasableId()));
        $this->assertEquals(2, $item->quantity());
    }
}
