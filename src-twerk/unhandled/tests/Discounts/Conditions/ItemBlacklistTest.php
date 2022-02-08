<?php

namespace Tests\Discounts\Conditions;

use Money\Money;
use Optiphar\Cart\Cart;
use Optiphar\Cart\CartReference;
use Optiphar\Cart\Http\CurrentCart;
use Optiphar\Discounts\Conditions\ItemBlacklist;
use Optiphar\Promos\Common\Domain\Discount\DiscountPercentage;
use Optiphar\Promos\Coupon\Domain\DisallowedProducts;
use Optiphar\Promos\Coupon\Infrastructure\DisallowedProducts\InMemoryDisallowedProducts;
use Tests\Factories\CartFactory;
use Tests\Factories\ProductFactory;
use Tests\Factories\PromoFactory;
use Tests\OptipharDatabaseTransactions;
use Tests\TestCase;
use Thinktomorrow\Trader\Tests\Cart\CartHelpers;

class ItemBlacklistTest extends TestCase
{
    use OptipharDatabaseTransactions, CartHelpers;

    public function setUp()
    {
        parent::setUp();

        $this->app->bind(DisallowedProducts::class, function ($app) {
            return (new InMemoryDisallowedProducts())->add(123456);
        });
    }

    /** @test */
    public function item_discount_passes_if_no_blacklist_is_enforced()
    {
        $condition = new ItemBlacklist([]);
        $cart = Cart::empty(CartReference::fromString('xxx'));
        $cart->items()->add(CartFactory::makeItem());

        $this->assertTrue($condition->check($cart, $cart->items()->first()));
    }

    /** @test */
    public function item_discount_passes_if_given_item_is_not_in_blacklist()
    {
        $condition = new ItemBlacklist(['123456']);
        $cart = Cart::empty(CartReference::fromString('xxx'));
        $cart->items()->add(CartFactory::makeItem(['product_id' => 99999]));

        $this->assertTrue($condition->check($cart, $cart->items()->first()));
    }

    /** @test */
    public function item_discount_does_not_pass_if_given_item_is_in_blacklist()
    {
        $condition = new ItemBlacklist(['123456']);
        $cart = Cart::empty(CartReference::fromString('xxx'));
        $cart->items()->add(CartFactory::makeItem(['product_id' => 123456]));

        $this->assertFalse($condition->check($cart, $cart->items()->first()));
    }

    /** @test */
    public function item_discount_is_applied_if_given_item_is_not_in_blacklist()
    {
        $this->setUpDatabase();
        $this->provideCartDataWithoutProducts();

        $product = ProductFactory::create(['id' => 9999, 'slug:nl' => 'xxx', 'gross_amount' => 500])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(10)], []);

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
    public function item_discount_is_not_applied_if_given_item_is_in_blacklist()
    {
        $this->setUpDatabase();
        $this->provideCartDataWithoutProducts();

        $product = ProductFactory::create(['id' => 123456, 'slug:nl' => 'xxx', 'gross_amount' => 500])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(10)], []);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');

        $cart = app(CurrentCart::class)->get();

        $this->assertCount(0, $cart->discounts());
        $this->assertEquals(Money::EUR(500), $cart->total());
        $this->assertEquals(Money::EUR(500), $cart->subTotal());
        $this->assertEquals(Money::EUR(0), $cart->discountTotal());
    }

    /** @test */
    public function a_discount_uses_the_blacklist_for_calculating_discount_baseprice()
    {
        $this->setUpDatabase();
        $this->provideCartDataWithoutProducts();

        $product = ProductFactory::create(['id' => 123456, 'slug:nl' => 'xxx', 'gross_amount' => 500])->withBrand()->toProductRead();
        $product2 = ProductFactory::create(['slug:nl' => 'xxx', 'gross_amount' => 200])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()
            ->addProduct($product)
            ->addProduct($product2)
            ->get();

        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(10)], []);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');

        $cart = app(CurrentCart::class)->get();

        $this->assertCount(1, $cart->discounts());
        $this->assertEquals(Money::EUR(20), $cart->discounts()->first()->total());
        $this->assertEquals(Money::EUR(200), $cart->discounts()->first()->baseTotal(true));
        $this->assertEquals(Money::EUR(680), $cart->total());
        $this->assertEquals(Money::EUR(700), $cart->subTotal());
        $this->assertEquals(Money::EUR(20), $cart->discountTotal());
    }
}
