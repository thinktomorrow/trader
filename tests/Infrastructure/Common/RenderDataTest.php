<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Common;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\TraderConfig;
use Thinktomorrow\Trader\Application\Common\DefaultLocale;

class RenderDataTest extends TestCase
{
    /** @test */
    public function it_can_render_data()
    {
        $stub = new RenderDataStub(['foo' => 'bar']);

        $this->assertEquals('bar', $stub->get('foo'));
    }

    /** @test */
    public function if_data_is_not_found_it_returns_null()
    {
        $stub = new RenderDataStub(['foo' => 'bar']);

        $this->assertNull($stub->get('xxx'));
    }

    /** @test */
    public function it_can_render_localized_content()
    {
        $stub = new RenderDataStub(['foo' => ['nl' => 'bar', 'en' => 'ber']]);

        $this->assertEquals('bar', $stub->get('foo', 'nl'));
        $this->assertEquals('ber', $stub->get('foo', 'en'));
    }

    /** @test */
    public function it_can_render_default_localized_content_based_on_current_app_locale()
    {
        $stub = new RenderDataStub(['foo' => ['nl' => 'bar', 'en' => 'ber']]);

        app()->setLocale('nl');
        DefaultLocale::set(app()->make(TraderConfig::class)->getDefaultLocale());

        $this->assertEquals('bar', $stub->get('foo'));

        app()->setLocale('en');
        DefaultLocale::set(app()->make(TraderConfig::class)->getDefaultLocale());

        $this->assertEquals('ber', $stub->get('foo'));
    }

    /** @test */
    public function it_can_render_default()
    {
        $stub = new RenderDataStub(['foo' => 'bar']);

        $this->assertEquals('fallback', $stub->get('xxx', null, 'fallback'));
    }

    /** @test */
    public function it_can_render_data_with_dotted_syntax()
    {
        $stub = new RenderDataStub(['foo' => ['fab' => 'bar']]);

        $this->assertEquals(['fab' => 'bar'], $stub->get('foo'));
        $this->assertEquals('bar', $stub->get('foo.fab'));
    }
}
