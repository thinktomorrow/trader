<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Thinktomorrow\Trader\Discounts\Domain\DiscountDescription;

class DiscountDescriptionTest extends UnitTestCase
{
    /** @test */
    function it_can_create_a_description()
    {
        $description = new DiscountDescription('foobar', ['baz' => 'bam']);

        $this->assertEquals('foobar',$description->type());
        $this->assertEquals(['baz' => 'bam'],$description->values());
    }

    /** @test */
    function it_prints_out_the_type()
    {
        $description = new DiscountDescription('foobar', ['baz' => 'bam']);

        $this->assertEquals('foobar',$description->__toString());
    }
}