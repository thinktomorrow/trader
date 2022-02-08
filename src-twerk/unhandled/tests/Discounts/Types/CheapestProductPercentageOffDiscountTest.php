<?php

namespace Thinktomorrow\Trader\Tests\Discounts\Types;

use Money\Money;
use Optiphar\Cart\Http\CurrentCart;
use Optiphar\Cashier\Percentage;
use Optiphar\Discounts\DiscountId;
use Optiphar\Discounts\Types\CheapestProductPercentageOffDiscount;
use Optiphar\Promos\Common\Domain\Discount\CheapestProductDiscountPercentage;
use Optiphar\Promos\Common\Domain\Discount\ExtraProductDiscountPercentage;
use Optiphar\Promos\Common\Domain\Rules\IsCombinable;
use Tests\Factories\CartFactory;
use Tests\Factories\ProductFactory;
use Tests\Factories\PromoFactory;
use Tests\OptipharDatabaseTransactions;
use Tests\TestCase;
use Thinktomorrow\Trader\Tests\Cart\CartHelpers;

class CheapestProductPercentageOffDiscountTest extends TestCase
{
    use OptipharDatabaseTransactions, CartHelpers;

    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->provideCartData();
    }

    /** @test */
    public function percentage_discount_cannot_be_negative()
    {
        $this->expectException(\InvalidArgumentException::class);

        new CheapestProductPercentageOffDiscount(DiscountId::fromString('xxx'), Percentage::fromPercent(-10), [], []);
    }

    /** @test */
    public function percentage_discount_cannot_exceed_100_percent()
    {
        $this->expectException(\InvalidArgumentException::class);

        new CheapestProductPercentageOffDiscount(DiscountId::fromString('xxx'), Percentage::fromPercent(101), [], []);
    }

    /** @test */
    public function percentage_is_subtracted_from_original_price()
    {
        $product = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo([
            'discount' => new CheapestProductDiscountPercentage(30),
        ], []);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertCount(0, $cart->discounts());
        $this->assertEquals(Money::EUR(70), $cart->total());
        $this->assertEquals(Money::EUR(0), $cart->discountTotal());

        $this->assertCount(1, $cart->items()->discounts());
        $this->assertEquals(Money::EUR(70), $cart->items()->first()->total());
        $this->assertEquals(Money::EUR(30), $cart->items()->first()->discountTotal());
    }

    /** @test */
    public function percentage_is_only_subtracted_from_cheapest_product()
    {
        $product = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();
        $product2 = ProductFactory::create(['gross_amount' => 200])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()
            ->addProduct($product)
            ->addProduct($product2)
            ->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo([
            'discount' => new CheapestProductDiscountPercentage(30),
        ], []);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertCount(0, $cart->discounts());
        $this->assertEquals(Money::EUR(270), $cart->total());
        $this->assertEquals(Money::EUR(0), $cart->discountTotal());

        $this->assertCount(1, $cart->items()->first()->discounts());
        $this->assertEquals(Money::EUR(70), $cart->items()->first()->total());
        $this->assertEquals(Money::EUR(30), $cart->items()->first()->discountTotal());

        $this->assertCount(0, $cart->items()[$product2->id]->discounts());
        $this->assertEquals(Money::EUR(200), $cart->items()[$product2->id]->total());
        $this->assertEquals(Money::EUR(0), $cart->items()[$product2->id]->discountTotal());
    }

    /** @test */
    public function discount_can_reduce_total_to_zero()
    {
        $product = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo([
            'discount' => new CheapestProductDiscountPercentage(100),
        ], []);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertCount(0, $cart->discounts());
        $this->assertEquals(Money::EUR(0), $cart->total());
        $this->assertEquals(Money::EUR(0), $cart->discountTotal());

        $this->assertCount(1, $cart->items()->first()->discounts());
        $this->assertEquals(Money::EUR(0), $cart->items()->first()->total());
        $this->assertEquals(Money::EUR(100), $cart->items()->first()->discountTotal());
    }

    /** @test */
    public function when_promo_is_highest_and_not_combinable_it_remains_the_only_promo_in_the_basket()
    {
        $product = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();
        $cheapestProduct = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()
                            ->addProduct($product)
                            ->addProduct($cheapestProduct)
                            ->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo([
            'discount' => new CheapestProductDiscountPercentage(5),
        ], []);
        PromoFactory::createOrderPromo([
            'discount' => new CheapestProductDiscountPercentage(30),
        ], []);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertEquals(Money::EUR(100 + (100 * 0.7)), $cart->total()); // 30% discount
    }

    /** @test */
    public function promo_only_applies_to_one_quantity_of_the_product()
    {
        $product = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();

        $cart = CartFactory::createEmpty()
                    ->addProduct($product)
                    ->addProduct($product)
                    ->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo([
            'discount' => new CheapestProductDiscountPercentage(30),
        ], []);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertCount(1, $cart->items());
        $this->assertEquals(2, $cart->quantity());
        $this->assertEquals(Money::EUR(170), $cart->total()); // 30% discount on one product
    }


    /** @test */
    public function two_cumulative_order_promos_for_cheapest_product_discounts_add_two_discounts()
    {
        $product = ProductFactory::create()->withBrand()->toProductRead();
        $cheapestproduct = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();

        $cart = CartFactory::createEmpty()
                    ->addProduct($product)
                    ->addProduct($cheapestproduct)
                    ->get();

        app(CurrentCart::class)->save($cart);

        $promo = PromoFactory::createOrderPromo(['discount' => new ExtraProductDiscountPercentage(60)], [
            'IsCombinable' => new IsCombinable(),
        ]);

        $promo2 = PromoFactory::createOrderPromo(['discount' => new CheapestProductDiscountPercentage(60)], [
            'IsCombinable' => new IsCombinable(),
        ]);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertCount(2, $cart->items());
        $this->assertCount(2, $cart->items()->discounts());
        $this->assertEquals(Money::EUR(200 * 0.4), $cart->total()); // 60% discount on first product, 100% on second
    }

    /** @test */
    public function two_cumulative_order_promos_with_same_cheapest_product_only_add_this_discount_once_to_same_product()
    {
        $cheapestproduct = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();

        $cart = CartFactory::createEmpty()
                ->addProduct($cheapestproduct)
                ->get();

        app(CurrentCart::class)->save($cart);

        $promo = PromoFactory::createOrderPromo(['discount' => new CheapestProductDiscountPercentage(30)], [
            'IsCombinable' => new IsCombinable(),
        ]);

        $promo2 = PromoFactory::createOrderPromo(['discount' => new CheapestProductDiscountPercentage(30)], [
            'IsCombinable' => new IsCombinable(),
        ]);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertEquals(Money::EUR(100 * 0.4), $cart->total()); // 30% discount
    }
}
