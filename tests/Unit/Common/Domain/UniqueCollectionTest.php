<?php

use Thinktomorrow\Trader\Common\Domain\UniqueCollection;
use Thinktomorrow\Trader\Tests\Unit\UnitTestCase;

class UniqueCollectionTest extends UnitTestCase
{
    private $discount;
    private $discount2;

    private function getCollection()
    {
        $this->discount = $this->makeDiscount(1);
        $this->discount2 = $this->makeDiscount(2);

        return new UniqueCollection([$this->discount, $this->discount2]);
    }

    /** @test */
    public function it_can_count_the_values()
    {
        $collection = $this->getCollection();

        $this->assertCount(2, $collection);
        $this->assertCount(2, $collection->all());
        $this->assertEquals(2, $collection->size());
    }

    /** @test */
    public function it_can_check_if_collection_contains_items()
    {
        $collection = $this->getCollection();

        $this->assertTrue($collection->any());
        $this->assertFalse((new UniqueCollection())->any());
    }

    /** @test */
    public function it_can_get_value_by_id_key()
    {
        $collection = $this->getCollection();

        $this->assertSame($this->discount, $collection->find(1));
        $this->assertSame($this->discount, $collection[1]);

        $this->assertNull($collection->find(9));
    }

    /** @test */
    public function it_can_add_an_item()
    {
        $discount = $this->makeDiscount(5);
        $collection = $this->getCollection();
        $collection->add($discount);

        $this->assertEquals(3, $collection->size());
        $this->assertSame($discount, $collection[5]);
    }

    /** @test */
    public function it_can_add_many_items_at_once()
    {
        $collection = $this->getCollection();

        $discount = $this->makeDiscount(5);
        $discount2 = $this->makeDiscount(10);
        $collection->addMany([$discount, $discount2]);

        $this->assertEquals(4, $collection->size());
        $this->assertSame($discount, $collection[5]);
        $this->assertSame($discount2, $collection[10]);
    }

    /** @test */
    public function it_can_set_item_by_key()
    {
        $collection = $this->getCollection();
        $collection[3] = $discount = $this->makeDiscount(3);

        $this->assertCount(3, $collection);
        $this->assertSame($discount, $collection[3]);
    }

    /** @test */
    public function it_can_only_set_item_by_key_if_it_matches_the_identifier()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        $collection = $this->getCollection();
        $collection[3] = $discount = $this->makeDiscount(4);
    }

    /** @test */
    public function key_must_be_valid_object_identifier()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $collection = $this->getCollection();
        $collection[3] = 'foobar';
    }

    /** @test */
    public function setting_item_must_have_explicit_key()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $collection = $this->getCollection();
        $collection[] = $this->makeDiscount(4);
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
