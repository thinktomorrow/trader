<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Cart\Discounts;

class MinimumItemsTest
{
    /** @test */
    public function item_discount_is_applied_if_minimum_items_has_been_reached()
    {
        $this->setUpDatabase();
        $this->provideCartData();

        $product = ProductFactory::create(['gross_amount' => 250])->withBrand()->toProductRead();
        $product2 = ProductFactory::create(['gross_amount' => 250])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()
            ->addProduct($product)
            ->addProduct($product2)
            ->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(10)], [
            'MinimumItems' => new \Optiphar\Promos\Common\Domain\Rules\MinimumItems(2),
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
    public function item_discount_is_only_applied_for_whitelisted_items()
    {
        $this->disableExceptionHandling();
        $this->setUpDatabase();
        $this->provideCartData();

        $product = ProductFactory::create(['gross_amount' => 500, 'base_discount_percentage' => 0])->withBrand()->toProductRead();
        $product2 = ProductFactory::create(['gross_amount' => 500, 'base_discount_percentage' => 0])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()
            ->addProduct($product)
            ->addProduct($product2)
            ->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(10)], [
            'ProductWhitelist' => new ProductWhitelist([$product->id], [], []),
            'MinimumItems' => new \Optiphar\Promos\Common\Domain\Rules\MinimumItems(1),
        ]);
        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');

        $cart = app(CurrentCart::class)->get();

        $this->assertCount(1, $cart->discounts());
        $this->assertEquals(Money::EUR(950), $cart->total());
        $this->assertEquals(Money::EUR(1000), $cart->subTotal());
        $this->assertEquals(Money::EUR(50), $cart->discountTotal());
    }

    /** @test */
    public function item_discount_is_not_applied_when_minimum_of_whitelisted_items_is_not_reached()
    {
        $this->disableExceptionHandling();
        $this->setUpDatabase();
        $this->provideCartData();

        $product = ProductFactory::create(['gross_amount' => 500, 'base_discount_percentage' => 0])->withBrand()->toProductRead();
        $product2 = ProductFactory::create(['gross_amount' => 500, 'base_discount_percentage' => 0])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()
            ->addProduct($product)
            ->addProduct($product2)
            ->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(10)], [
            'ProductWhitelist' => new ProductWhitelist([$product->id], [], []),
            'MinimumItems' => new \Optiphar\Promos\Common\Domain\Rules\MinimumItems(2),
        ]);
        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');

        $cart = app(CurrentCart::class)->get();

        $this->assertCount(0, $cart->discounts());
        $this->assertEquals(Money::EUR(1000), $cart->total());
    }
}
