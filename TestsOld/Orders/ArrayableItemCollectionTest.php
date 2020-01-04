<?php

use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\ItemCollection;
use Thinktomorrow\Trader\TestsOld\Stubs\PurchasableStub;
use Thinktomorrow\Trader\TestsOld\TestCase;

class ArrayableItemCollectionTest extends TestCase
{
    private function getCollection()
    {
        return new ItemCollection(
            $this->getItem(null, null, new PurchasableStub(1)),
            $this->getItem(null, null, new PurchasableStub(2))
        );
    }

    /** @test */
    public function it_can_count_the_values()
    {
        $collection = $this->getCollection();

        $this->assertCount(2, $collection);
    }

    /** @test */
    public function it_can_get_value_by_key()
    {
        $collection = $this->getCollection();

        $this->assertInstanceOf(Item::class, $collection[1]);
    }

    /** @test */
    public function it_can_set_item_by_key()
    {
        $collection = $this->getCollection();
        $collection[3] = $item = $this->getItem(null, null, new PurchasableStub(1));

        $this->assertCount(3, $collection);
        $this->assertSame($item, $collection[3]);
    }

    /** @test */
    public function set_item_by_key_must_be_item()
    {
        $this->expectException(InvalidArgumentException::class);

        $collection = $this->getCollection();
        $collection[3] = 'foobar';
    }

    /** @test */
    public function setting_item_must_have_explicit_key()
    {
        $this->expectException(InvalidArgumentException::class);

        $collection = $this->getCollection();
        $collection[] = $this->getItem(null, null, new PurchasableStub(1));
    }

    /** @test */
    public function it_can_unset_a_value()
    {
        $collection = $this->getCollection();

        $this->assertCount(2, $collection);
        unset($collection[1]);

        $this->assertCount(1, $collection);
    }

    /** @test */
    public function it_can_check_if_key_exists()
    {
        $collection = $this->getCollection();

        $this->assertTrue(isset($collection[1]));
        $this->assertFalse(isset($collection[3]));
    }

    /** @test */
    public function it_can_loop_over_collection()
    {
        $collection = $this->getCollection();

        $flag = 0;
        foreach ($collection as $item) {
            $flag++;
        }

        $this->assertEquals(2, $flag);
    }
}
