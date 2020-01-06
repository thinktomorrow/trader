<?php

namespace Tests\Integration;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Cookie\CookieJar;
use Thinktomorrow\Trader\Common\Ports\Laravel\CookieSource;

class CookieSourceTest extends TestCase
{
    private $cookieSource;

    public function setUp()
    {
        parent::setUp();

        $this->cookieSource = new class(app()->make('request'), $this->getCookieJar()) extends CookieSource { protected $cookieKey = 'monster'; protected $lifetime = '90000'; };
    }

    /** @test */
    function by_default_a_cookievalue_does_not_exist_and_returns_null()
    {
        $this->assertFalse($this->cookieSource->exists());
        $this->assertNull($this->cookieSource->get());
    }

    /** @test */
    function it_can_store_a_string_as_cookie_value()
    {
        $this->cookieSource->set('foobar');

        $this->assertEquals('foobar', $this->cookieSource->get());
    }

    /** @test */
    function it_can_store_a_quoted_string_as_cookie_value()
    {
        $this->cookieSource->set('"foobar"');

        $this->assertEquals('"foobar"', $this->cookieSource->get());
    }

    /** @test */
    function it_can_store_an_integer_as_cookie_value()
    {
        $this->cookieSource->set(1);

        $this->assertEquals(1, $this->cookieSource->get());
    }

    /** @test */
    function it_can_store_an_array_as_cookie_value()
    {
        $this->cookieSource->set([
            'cookie'    => 'monster'
        ]);

        $this->assertEquals([
            'cookie'    => 'monster'
        ], $this->cookieSource->get());
    }

    /** @test */
    function it_cannot_store_an_object_as_cookie_value()
    {
        $this->expectException(\TypeError::class);

        $this->cookieSource->set((object)[]);
    }

    /** @test */
    function assoc_array_stored_as_object_is_fetched_as_array()
    {
        $cookies = ['monster' => (object)['foo' => 'bar']];

        // Simulate add to cart and visit the site a second time
        $this->call('GET', '/foo', [], $cookies);
        $this->cookieSource = new class(app()->make('request'), $this->getCookieJar()){ use CookieValue; protected $cookieKey = 'monster'; protected $lifetime = '90000';};

        $this->assertTrue($this->cookieSource->exists());
        $this->assertEquals(['foo' => 'bar'], $this->cookieSource->get());
    }

    private function getCookieJar()
    {
        return new CookieJar(Request::create('/foo', 'GET'), [
            'path'     => '/path',
            'domain'   => '/domain',
            'secure'   => true,
            'httpOnly' => false,
        ]);
    }

}
