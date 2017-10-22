<?php

use Thinktomorrow\Trader\Common\Domain\Description;
use Thinktomorrow\Trader\Common\Domain\DescriptionRender;
use Thinktomorrow\Trader\Tests\Stubs\InMemoryDescriptionRender;
use Thinktomorrow\Trader\Tests\UnitTestCase;

class DescriptionRenderTest extends UnitTestCase
{
    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(DescriptionRender::class, new InMemoryDescriptionRender());
    }

    /** @test */
    public function if_description_is_not_set_empty_string_is_returned()
    {
        $render = new InMemoryDescriptionRender();

        $this->assertEquals('', $render->locale());
    }

    /** @test */
    public function it_can_render_a_translated_description_in_default_locale()
    {
        $render = new InMemoryDescriptionRender();
        $description = new Description('foobar', []);

        $this->assertEquals('Dit is een bericht', $render->description($description)->locale());
    }

    /** @test */
    public function it_can_render_a_description_in_specific_locale()
    {
        $render = new InMemoryDescriptionRender();
        $description = new Description('foobar', []);

        $this->assertEquals('This is a message', $render->description($description)->locale('en'));
    }
}
