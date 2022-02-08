<?php

namespace Thinktomorrow\Trader\Tests\Discounts\Types;

use Money\Money;
use Optiphar\Cart\Http\CurrentCart;
use Optiphar\Cashier\Percentage;
use Optiphar\Discounts\DiscountId;
use Optiphar\Discounts\Types\ProductPercentageOffDiscount;
use Optiphar\Promos\Common\Domain\Discount\ExtraProductDiscountPercentage;
use Optiphar\Promos\Common\Domain\Rules\ProductWhitelist;
use Tests\Factories\CartFactory;
use Tests\Factories\ProductFactory;
use Tests\Factories\PromoFactory;
use Tests\OptipharDatabaseTransactions;
use Tests\TestCase;
use Thinktomorrow\Trader\Tests\Cart\CartHelpers;

class ExtraProductPercentageOffDiscountTest extends TestCase
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

        new ProductPercentageOffDiscount(DiscountId::fromString('xxx'), Percentage::fromPercent(-10), [], []);
    }

    /** @test */
    public function percentage_discount_cannot_exceed_100_percent()
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProductPercentageOffDiscount(DiscountId::fromString('xxx'), Percentage::fromPercent(101), [], []);
    }

    /** @test */
    public function percentage_is_subtracted_from_original_price()
    {
        $product = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo([
            'discount' => new ExtraProductDiscountPercentage(30),
        ], []);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertCount(0, $cart->discounts());
        $this->assertEquals(Money::EUR(70), $cart->total());
        $this->assertEquals(Money::EUR(0), $cart->discountTotal());

        $this->assertCount(1, $cart->items()->first()->discounts());
        $this->assertEquals(Money::EUR(70), $cart->items()->first()->total());
        $this->assertEquals(Money::EUR(30), $cart->items()->first()->discountTotal());
    }

    /** @test */
    public function percentage_can_be_set_for_specific_products()
    {
        $product = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();
        $product2 = ProductFactory::create(['gross_amount' => 200])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()
            ->addProduct($product)
            ->addProduct($product2)
            ->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo([
            'discount' => new ExtraProductDiscountPercentage(30),
        ], [
            'ProductWhitelist' => new ProductWhitelist([$product->id], [], []),
        ]);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertCount(0, $cart->discounts());
        $this->assertEquals(Money::EUR(270), $cart->total());
        $this->assertEquals(Money::EUR(0), $cart->discountTotal());

        $this->assertCount(1, $cart->items()->first()->discounts());
        $this->assertEquals(Money::EUR(70), $cart->items()->first()->total());
        $this->assertEquals(Money::EUR(30), $cart->items()->first()->discountTotal());
    }

    /** @test */
    public function discount_can_reduce_total_to_zero()
    {
        $product = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo([
            'discount' => new ExtraProductDiscountPercentage(100),
//            'minimumAmount' => MinimumAmount::fromEUR(50)
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
}
