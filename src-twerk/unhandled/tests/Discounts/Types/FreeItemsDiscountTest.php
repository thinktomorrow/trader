<?php

namespace Thinktomorrow\Trader\Tests\Discounts\Types;

use Money\Money;
use Optiphar\Cart\Http\CurrentCart;
use Optiphar\Promos\Common\Domain\Discount\FreeProductDiscount;
use Optiphar\Promos\Common\Domain\Rules\IsCombinable;
use Tests\Factories\CartFactory;
use Tests\Factories\ProductFactory;
use Tests\Factories\PromoFactory;
use Tests\OptipharDatabaseTransactions;
use Tests\TestCase;
use Thinktomorrow\Trader\Tests\Cart\CartHelpers;

class FreeItemsDiscountTest extends TestCase
{
    use OptipharDatabaseTransactions, CartHelpers;

    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->provideCartData();
    }

    /** @test */
    public function free_items_are_added_to_cart()
    {
        $product = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();
        $freeProduct = ProductFactory::create(['gross_amount' => 50])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo([
            'discount' => new FreeProductDiscount($freeProduct->id),
        ], []);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertCount(2, $cart->items());

        $freeCartItem = $cart->items()->find('free-'.$freeProduct->id);
        $this->assertCount(1, $freeCartItem->discounts());
        $this->assertEquals(Money::EUR(0), $freeCartItem->total());
        $this->assertEquals(Money::EUR(50), $freeCartItem->discountTotalAsMoney());

        $this->assertCount(0, $cart->discounts());
        $this->assertEquals(Money::EUR(100), $cart->total());
        $this->assertEquals(Money::EUR(0), $cart->discountTotal());
    }

    /** @test */
    public function it_can_add_same_product_for_free_but_adds_it_as_separate_item()
    {
        $product = $freeProduct = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo([
            'discount' => new FreeProductDiscount($freeProduct->id),
        ], []);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertCount(2, $cart->items());

        $freeCartItem = $cart->items()->find('free-'.$freeProduct->id);
        $this->assertCount(1, $freeCartItem->discounts());
        $this->assertCount(1, $cart->items()->discounts());
        $this->assertEquals(Money::EUR(0), $freeCartItem->total());
        $this->assertEquals(Money::EUR(100), $freeCartItem->discountTotalAsMoney());
        $this->assertEquals(Money::EUR(100), $freeCartItem->subTotal());
        $this->assertEquals(Money::EUR(100), $freeCartItem->salePrice(true));

        $this->assertCount(0, $cart->discounts());
        $this->assertEquals(Money::EUR(100), $cart->total());
        $this->assertEquals(Money::EUR(0), $cart->discountTotal());
    }

    /** @test */
    public function it_keeps_free_product_as_separate_item()
    {
        $product = $freeProduct = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo([
            'discount' => new FreeProductDiscount($freeProduct->id),
        ], []);

        // Trigger the cart middleware for the first time to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertCount(2, $cart->items());
        $this->assertEquals(Money::EUR(100), $cart->total());

        // Save the current state of the cart with the free item
        app(CurrentCart::class)->save($cart);

        $cart = app(CurrentCart::class)->get();

        $this->assertCount(2, $cart->items());
        $this->assertEquals(Money::EUR(100), $cart->total());
    }

    /** @test */
    public function two_cumulative_order_promos_with_same_free_product_add_this_product_twice()
    {
        $product = ProductFactory::create()->withBrand()->toProductRead();
        $freeProduct = ProductFactory::create(['gross_amount' => 50])->withBrand()->toProductRead();

        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo([
            'discount' => new FreeProductDiscount($freeProduct->id),
        ], [
            'IsCombinable' => new IsCombinable(),
        ]);

        PromoFactory::createOrderPromo([
            'discount' => new FreeProductDiscount($freeProduct->id),
        ], [
            'IsCombinable' => new IsCombinable(),
        ]);

        // Trigger the cart middleware for the first time to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertCount(2, $cart->items());
        $this->assertEquals(2, $cart->items()->first(function ($basketProduct) use ($freeProduct) {
            return $basketProduct->productId() == $freeProduct->id;
        })->quantity());
        $this->assertEquals(Money::EUR(200), $cart->total());
    }

    /** @test */
    public function if_promo_is_not_combinable_it_remains_the_only_promo_in_the_basket()
    {
        $product = ProductFactory::create()->withBrand()->toProductRead();
        $freeProduct = ProductFactory::create()->withBrand()->toProductRead();

        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo([
            'discount' => new FreeProductDiscount($freeProduct->id),
        ], []);
        PromoFactory::createOrderPromo([
            'discount' => new FreeProductDiscount($freeProduct->id),
        ], []);

        // Trigger the cart middleware for the first time to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertCount(2, $cart->items());
        $this->assertEquals(1, $cart->items()->first(function ($basketProduct) use ($freeProduct) {
            return $basketProduct->productId() == $freeProduct->id;
        })->quantity());
        $this->assertEquals(Money::EUR(200), $cart->total());
    }
}
