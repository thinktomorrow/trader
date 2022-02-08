<?php

namespace Thinktomorrow\Trader\Tests\Order;

use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;
use Thinktomorrow\Trader\Cart\Ports\OrderReferenceCookie;
use Thinktomorrow\Trader\Order\Domain\OrderReference;

class OrderReferenceCookieTest extends TestCase
{
    /** @test */
    public function it_has_no_reference_by_default()
    {
        $orderReference = $this->orderReference($this->getCookieJar());

        $this->assertFalse($orderReference->exists());
    }

    /** @test */
    public function it_catches_reference_from_cookie()
    {
        $this->setCookieOnResponse(OrderReferenceCookie::KEY, 'foobar');
        $orderReference = $this->orderReference($this->getCookieJar());

        $this->assertTrue($orderReference->exists());
        $this->assertEquals('foobar', $orderReference->get());
    }

    /** @test */
    public function it_can_save_new_reference_to_cookie_queue()
    {
        $this->setCookieOnResponse(OrderReferenceCookie::KEY, 'foobar');
        $orderReference = $this->orderReference($cookieJar = $this->getCookieJar());

        $this->assertEquals(OrderReference::fromString('foobar'), $orderReference->get());

        $orderReference->set(OrderReference::fromString('new'));
        $this->assertEquals(OrderReference::fromString('new'), $orderReference->get());

        $this->assertCount(1, $cookieJar->getQueuedCookies());

        /** @var Cookie $firstCookie */
        $firstCookie = $cookieJar->getQueuedCookies()[0];

        $this->assertEquals(OrderReferenceCookie::KEY, $firstCookie->getName());
        $this->assertEquals('new', $firstCookie->getValue());
    }

    private function orderReference($cookieJar)
    {
        return new OrderReferenceCookie(app()->make('request'), $cookieJar);
    }

    private function getCookieJar()
    {
        return new CookieJar(Request::create('/foo', 'GET'), [
            'path' => '/path',
            'domain' => '/domain',
            'secure' => true,
            'httpOnly' => false,
        ]);
    }
}
