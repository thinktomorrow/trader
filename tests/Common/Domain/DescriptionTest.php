<?php

use Thinktomorrow\Trader\Common\Domain\Description;
use Thinktomorrow\Trader\Tests\UnitTestCase;

class DescriptionTest extends UnitTestCase
{
    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(Description::class, new Description('foobar', ['one', 'two']));
    }

    /** @test */
    public function it_exposes_the_key_and_values()
    {
        $description = new Description('foobar', ['one', 'two']);

        $this->assertEquals('foobar', $description->key());
        $this->assertEquals(['one', 'two'], $description->values());
    }

    /** @test */
    public function class_prints_out_as_key()
    {
        $description = new Description('foobar', ['one', 'two']);

        $this->assertEquals('foobar', $description);
    }
}
