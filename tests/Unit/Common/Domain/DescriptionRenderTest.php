<?php

use Thinktomorrow\Trader\Common\Domain\Description;
use Thinktomorrow\Trader\Common\Domain\DescriptionRender;
use Thinktomorrow\Trader\Tests\Unit\Stubs\InMemoryDescriptionRender;
use Thinktomorrow\Trader\Tests\Unit\UnitTestCase;

class DescriptionRenderTest extends UnitTestCase
{
    /** @test */
    function it_can_be_instantiated()
    {
        $this->assertInstanceOf(DescriptionRender::class, new InMemoryDescriptionRender());
    }

    /** @test */
    function if_description_is_not_set_empty_string_is_returned()
    {
        $render = new InMemoryDescriptionRender();

        $this->assertEquals('',$render->locale());
    }

    /** @test */
    function it_can_render_a_translated_description_in_default_locale()
    {
        $render = new InMemoryDescriptionRender();
        $description = new Description('foobar',[]);

        $this->assertEquals('Dit is een bericht',$render->description($description)->locale());
    }

    /** @test */
    function it_can_render_a_description_in_specific_locale()
    {
        $render = new InMemoryDescriptionRender();
        $description = new Description('foobar',[]);

        $this->assertEquals('This is a message',$render->description($description)->locale('en'));
    }
}
