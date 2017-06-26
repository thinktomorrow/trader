<?php

use Thinktomorrow\Trader\Tests\Unit\UnitTestCase;

use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Domain\ItemCollection;
use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePurchasable;

class ArrayableItemCollectionTest extends UnitTestCase
{
    private function getCollection()
    {
        return new ItemCollection(
            Item::fromPurchasable(new ConcretePurchasable(1)),
            Item::fromPurchasable(new ConcretePurchasable(2))
        );
    }

    /** @test */
    function it_can_count_the_values()
    {
        $collection = $this->getCollection();

        $this->assertCount(2,$collection);
    }

    /** @test */
    function it_can_get_value_by_key()
    {
        $collection = $this->getCollection();

        $this->assertInstanceOf(Item::class,$collection[1]);
    }

    /** @test */
    function it_can_set_item_by_key()
    {
        $collection = $this->getCollection();
        $collection[3] = $item = Item::fromPurchasable(new ConcretePurchasable(1));

        $this->assertCount(3,$collection);
        $this->assertSame($item,$collection[3]);
    }

    /** @test */
    function set_item_by_key_must_be_item()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $collection = $this->getCollection();
        $collection[3] = 'foobar';
    }

    /** @test */
    function setting_item_must_have_explicit_key()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $collection = $this->getCollection();
        $collection[] = Item::fromPurchasable(new ConcretePurchasable(1));;
    }

    /** @test */
    function it_can_unset_a_value()
    {
        $collection = $this->getCollection();

        $this->assertCount(2,$collection);
        unset($collection[1]);

        $this->assertCount(1,$collection);
    }

    /** @test */
    function it_can_check_if_key_exists()
    {
        $collection = $this->getCollection();

        $this->assertTrue(isset($collection[1]));
        $this->assertFalse(isset($collection[3]));
    }

    /** @test */
    function it_can_loop_over_collection()
    {
        $collection = $this->getCollection();

        $flag = 0;
        foreach($collection as $item){ $flag++; }

        $this->assertEquals(2,$flag);
    }
}