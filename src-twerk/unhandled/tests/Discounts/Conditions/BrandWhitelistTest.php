<?php

namespace Tests\Discounts\Conditions;

use Money\Money;
use Optiphar\Cart\Cart;
use Optiphar\Cart\CartReference;
use Optiphar\Cart\Http\CurrentCart;
use Optiphar\Discounts\Conditions\BrandWhitelist;
use Optiphar\Promos\Common\Domain\Discount\DiscountPercentage;
use Optiphar\Promos\Common\Domain\Rules\ProductWhitelist;
use Tests\Factories\CartFactory;
use Tests\Factories\ProductFactory;
use Tests\Factories\PromoFactory;
use Tests\OptipharDatabaseTransactions;
use Tests\TestCase;
use Thinktomorrow\Trader\Tests\Cart\CartHelpers;

class BrandWhitelistTest extends TestCase
{
    use OptipharDatabaseTransactions, CartHelpers;

    /** @test */
    public function brand_whitelist_passes_if_no_whitelist_is_enforced()
    {
        $condition = new BrandWhitelist([]);
        $cart = Cart::empty(CartReference::fromString('xxx'));
        $cart->items()->add(CartFactory::makeItem());

        $this->assertTrue($condition->check($cart, $cart->items()->first()));
    }

    /** @test */
    public function brand_whitelist_passes_if_given_item_is_in_whitelist()
    {
        $condition = new BrandWhitelist(['123456']);
        $cart = Cart::empty(CartReference::fromString('xxx'));
        $cart->items()->add(CartFactory::makeItem(['brand_id' => 123456]));

        $this->assertTrue($condition->check($cart, $cart->items()->first()));
    }

    /** @test */
    public function brand_whitelist_does_not_pass_if_given_item_is_not_in_whitelist()
    {
        $condition = new BrandWhitelist(['123456']);
        $cart = Cart::empty(CartReference::fromString('xxx'));
        $cart->items()->add(CartFactory::makeItem(['brand_id' => 999999]));

        $this->assertFalse($condition->check($cart, $cart->items()->first()));
    }

    /** @test */
    public function brand_whitelist_is_applied_if_given_item_is_in_whitelist()
    {
        $this->setUpDatabase();
        $this->provideCartData();

        $product = ProductFactory::create(['slug:nl' => 'xxx', 'gross_amount' => 500])->withBrand(['id' => '123456'])->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(10)], ['ProductWhitelist' => new ProductWhitelist([], ['123456'], [])]);

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
    public function brand_whitelist_is_not_applied_if_given_item_is_not_in_whitelist()
    {
        $this->setUpDatabase();
        $this->provideCartData();

        $product = ProductFactory::create(['slug:nl' => 'xxx', 'gross_amount' => 500])->withBrand(['id' => '123456'])->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(10)], ['ProductWhitelist' => new ProductWhitelist([], ['987654321'], [])]);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');

        $cart = app(CurrentCart::class)->get();

        $this->assertCount(0, $cart->discounts());

        $this->assertEquals(Money::EUR(500), $cart->subTotal());
        $this->assertEquals(Money::EUR(500), $cart->total());
        $this->assertEquals(Money::EUR(0), $cart->discountTotal());
    }

    /** @test */
    public function a_discount_uses_the_whitelist_for_calculating_discount_baseprice()
    {
        $this->setUpDatabase();
        $this->provideCartData();

        $product = ProductFactory::create(['slug:nl' => 'xxx', 'gross_amount' => 500])->withBrand(['id' => '123456'])->toProductRead();
        $product2 = ProductFactory::create(['slug:nl' => 'xxx', 'gross_amount' => 200])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()
            ->addProduct($product)
            ->addProduct($product2)
            ->get();

        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(10)], ['ProductWhitelist' => new ProductWhitelist([], ['123456'], [])]);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');

        $cart = app(CurrentCart::class)->get();

        $this->assertCount(1, $cart->discounts());
        $this->assertEquals(Money::EUR(50), $cart->discounts()->first()->total());
        $this->assertEquals(Money::EUR(500), $cart->discounts()->first()->baseTotal(true));
        $this->assertEquals(Money::EUR(650), $cart->total());
        $this->assertEquals(Money::EUR(700), $cart->subTotal());
        $this->assertEquals(Money::EUR(50), $cart->discountTotal());
    }
}
