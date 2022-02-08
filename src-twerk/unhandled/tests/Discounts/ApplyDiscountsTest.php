<?php

namespace Thinktomorrow\Trader\Tests\Discounts;

use Money\Money;
use Optiphar\Cart\CartReference;
use Optiphar\Cart\Http\CurrentCart;
use Optiphar\Cart\Ports\DbCartRepository;
use Optiphar\Promos\Common\Domain\Discount\DiscountPercentage;
use Optiphar\Promos\Common\Domain\Discount\ExtraProductDiscountPercentage;
use Optiphar\Promos\Common\Domain\Rules\IsCombinable;
use Tests\Factories\CartFactory;
use Tests\Factories\ProductFactory;
use Tests\Factories\PromoFactory;
use Tests\OptipharDatabaseTransactions;
use Tests\TestCase;
use Thinktomorrow\Trader\Tests\Cart\CartHelpers;

class ApplyDiscountsTest extends TestCase
{
    use OptipharDatabaseTransactions,
        CartHelpers;

    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->provideCartData();
    }

    /** @test */
    public function it_can_collect_the_applicable_discounts_for_a_given_cart()
    {
        $product = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();

        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        $existingTotal = $cart->total();

        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(60)], []);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');

        $cart = app(CurrentCart::class)->get();

        $this->assertCount(1, $cart->discounts());
        $this->assertEquals($existingTotal->multiply(0.4), $cart->total());
        $this->assertEquals($existingTotal->multiply(0.6), $cart->discountTotal());
    }

    /** @test */
    public function it_can_collect_the_applicable_discounts_for_a_given_item()
    {
        $product = ProductFactory::create(['slug:nl' => 'hijkmk', 'gross_amount' => 100])->withBrand()->toProductRead();

        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        $existingTotal = $cart->total();

        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo(['discount' => new ExtraProductDiscountPercentage(60)], []);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');

        $cart = app(CurrentCart::class)->get();

        $this->assertCount(0, $cart->discounts());
        $this->assertEquals($existingTotal->multiply(0.4), $cart->total());
        $this->assertEquals(Money::EUR(0), $cart->discountTotal());

        $item = $cart->items()->first();
        $this->assertCount(1, $item->discounts());
        $this->assertEquals($existingTotal->multiply(0.4), $item->total());
        $this->assertEquals($existingTotal->multiply(0.6), $item->discountTotal());
    }

    /** @test */
    public function combinable_discounts_do_not_lower_total_below_zero()
    {
        $product = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(60)], []);
        PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(60)], [
            'IsCombinable' => new IsCombinable(),
        ]);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');

        $cart = app(CurrentCart::class)->get();

        $this->assertCount(2, $cart->discounts());
        $this->assertEquals(Money::EUR(0), $cart->total());
        $this->assertEquals(Money::EUR(100), $cart->discountTotal());
    }

    /** @test */
    public function applied_discounts_are_retrievable_as_stale_data()
    {
        $product = ProductFactory::create(['slug:nl' => 'hijkmk', 'gross_amount' => 100])->withBrand()->toProductRead();

        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        $existingTotal = $cart->total();

        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo(['discount' => new ExtraProductDiscountPercentage(60)], []);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');

        $cart = app(CurrentCart::class)->get();
        app(CurrentCart::class)->save($cart);

        $cart = app(DbCartRepository::class)->stale()->findByReference(CartReference::fromString('xxx'));

        $this->assertCount(0, $cart->discounts());
        $this->assertEquals($existingTotal->multiply(0.4), $cart->total());
        $this->assertEquals(Money::EUR(0), $cart->discountTotal());

        $item = $cart->items()->first();
        $this->assertCount(1, $item->discounts());
        $this->assertEquals($existingTotal->multiply(0.4), $item->total());
        $this->assertEquals($existingTotal->multiply(0.6), $item->discountTotal());
    }
}
