<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Cart\Discounts;

use Carbon\Carbon;
use Money\Money;

class PeriodTest
{
    /** @test */
    public function discount_is_applied_when_within_period()
    {
        $this->setUpDatabase();
        $this->provideCartData();

        $product = ProductFactory::create(['slug:nl' => 'xxx', 'gross_amount' => 500])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(10)], [
            'Period' => new \Optiphar\Promos\Common\Domain\Rules\Period(Carbon::now()->subDay(1), Carbon::now()->addDay(1)),
        ]);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');

        $cart = app(CurrentCart::class)->get();

        $this->assertCount(1, $cart->discounts());
        $this->assertEquals(Money::EUR(50), $cart->discounts()->first()->total());
        $this->assertEquals(Money::EUR(500), $cart->discounts()->first()->baseTotal(true));
        $this->assertEquals(Money::EUR(450), $cart->total());
        $this->assertEquals(Money::EUR(500), $cart->subTotal());
        $this->assertEquals(Money::EUR(50), $cart->discountTotal());
    }

    /** @test */
    public function discount_is_not_applied_when_outside_period()
    {
        $this->setUpDatabase();
        $this->provideCartData();

        $product = ProductFactory::create(['slug:nl' => 'xxx', 'gross_amount' => 500])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(10)], [
            'Period' => new \Optiphar\Promos\Common\Domain\Rules\Period(Carbon::now()->subDay(2), Carbon::now()->subDay(1)),
        ]);
        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');

        $cart = app(CurrentCart::class)->get();

        $this->assertCount(0, $cart->discounts());
        $this->assertEquals(Money::EUR(500), $cart->total());
        $this->assertEquals(Money::EUR(500), $cart->subTotal());
        $this->assertEquals(Money::EUR(0), $cart->discountTotal());
    }
}
