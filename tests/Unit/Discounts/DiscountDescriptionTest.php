<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Thinktomorrow\Trader\Common\Domain\Description;

class DiscountDescriptionTest extends UnitTestCase
{
    /** @test */
    function it_can_create_a_description()
    {
        $description = new Description('foobar', ['baz' => 'bam']);

        $this->assertEquals('foobar',$description->key());
        $this->assertEquals(['baz' => 'bam'],$description->values());
    }

    /** @test */
    function it_prints_out_the_key()
    {
        $description = new Description('foobar', ['baz' => 'bam']);

        $this->assertEquals('foobar',$description->__toString());
    }
}