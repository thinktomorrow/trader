<?php

namespace Thinktomorrow\Trader\Tests\Cart;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Thinktomorrow\Trader\Cart\Ports\CurrentCart;
use Thinktomorrow\Trader\Cart\Ports\DbCartRepository;
use Thinktomorrow\Trader\Cart\Ports\OrderReferenceCookie;
use Thinktomorrow\Trader\Order\Domain\OrderReference;

class CurrentCartTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_will_return_new_empty_cart()
    {
        $cart = app(CurrentCart::class)->get();

        $this->assertTrue($cart->isEmpty());
    }

    /** @test */
    public function it_can_retrieve_cart_by_reference()
    {
        // Store cart first
        $orderReference = OrderReference::fromString('xxx');
        $cart = app(DbCartRepository::class)->emptyCart($orderReference);
        app(DbCartRepository::class)->save($cart);

        app(CurrentCart::class)->setReference($orderReference);
        $storedCart = app(CurrentCart::class)->get();

        $this->assertEquals($cart->getReference(), $storedCart->getReference());
        $this->assertEquals($cart, $storedCart);
    }

    /** @test */
    public function if_reference_is_not_found_an_new_empty_cart_is_returned()
    {
        app(CurrentCart::class)->setReference(OrderReference::fromString('xxx'));
        $cart = app(CurrentCart::class)->get();

        $this->assertNotEquals(OrderReference::fromString('xxx'), $cart->getReference());
    }

    /** @test */
    public function it_can_retrieve_cart_from_cookie_reference()
    {
        $this->storeOrder('xxx');

        $this->setCookieOnResponse(OrderReferenceCookie::KEY, 'xxx');
        $this->get('/');

        $cart = app(CurrentCart::class)->get();
        $this->assertEquals('xxx', $cart->getReference()->get());
    }

    /** @test */
    public function cart_is_retrievable_across_multiple_sessions()
    {
        $this->get('/');
        $cart = app(CurrentCart::class)->get();

        $this->get('/');
        $this->assertEquals($cart->getReference(), app(CurrentCart::class)->get()->getReference());
    }
}
