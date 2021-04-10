<?php

namespace Thinktomorrow\Trader\Tests\Common;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Common\Collection;

class CollectionTest extends TestCase
{
    /** @test */
    public function it_can_set_item()
    {
        $collection = new Collection();
        $collection[] = 'first item';

        $this->assertEquals('first item', $collection[0]);
        $this->assertTrue(isset($collection[0]));
    }

    /** @test */
    public function it_can_remove_item()
    {
        $collection = new Collection();
        $collection[] = 'first item';

        unset($collection[0]);

        $this->assertFalse(isset($collection[0]));
    }

    /** @test */
    public function it_can_count_the_items()
    {
        $collection = new Collection();
        $collection[] = 'first item';
        $collection[] = 'second item';

        $this->assertEquals(2, count($collection));
    }

    /** @test */
    public function it_can_loop_over_items()
    {
        $collection = new Collection();
        $collection[] = 'first item';
        $collection[] = 'second item';

        $loops = 0;
        foreach($collection as $item){
            $loops++;
        }

        $this->assertEquals(2, $loops);
    }

    /** @test */
    public function it_can_map_the_items()
    {
        $collection = new Collection([
            'first item',
            'second item',
        ]);

        $collection = $collection->map(function(){ return 'foobar'; });

        $loops = 0;
        foreach($collection as $item){
            $this->assertEquals('foobar', $item);
            $loops++;
        }

        // Assert the loop has run
        $this->assertEquals(2, $loops);
    }
}
