<?php

namespace Thinktomorrow\Trader\Tests\Discounts\Types;

use Money\Money;
use Optiphar\Cart\Http\CurrentCart;
use Optiphar\Cashier\Percentage;
use Optiphar\Discounts\DiscountId;
use Optiphar\Discounts\Types\PercentageOffDiscount;
use Optiphar\Promos\Common\Domain\Discount\DiscountPercentage;
use Tests\Factories\CartFactory;
use Tests\Factories\ProductFactory;
use Tests\Factories\PromoFactory;
use Tests\OptipharDatabaseTransactions;
use Tests\TestCase;
use Thinktomorrow\Trader\Tests\Cart\CartHelpers;

class PercentageOffDiscountTest extends TestCase
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

        new PercentageOffDiscount(DiscountId::fromString('xxx'), Percentage::fromPercent(-10), [], []);
    }

    /** @test */
    public function percentage_discount_cannot_exceed_100_percent()
    {
        $this->expectException(\InvalidArgumentException::class);

        new PercentageOffDiscount(DiscountId::fromString('xxx'), Percentage::fromPercent(101), [], []);
    }

    /** @test */
    public function percentage_is_subtracted_from_original_price()
    {
        $product = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo([
            'discount' => new DiscountPercentage(30),
        ], []);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertCount(1, $cart->discounts());
        $this->assertEquals(Money::EUR(70), $cart->total());
        $this->assertEquals(Money::EUR(30), $cart->discountTotal());
    }

    /** @test */
    public function discount_can_reduce_total_to_zero()
    {
        $product = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo([
            'discount' => new DiscountPercentage(100),
        ], []);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertCount(1, $cart->discounts());
        $this->assertEquals(Money::EUR(0), $cart->total());
        $this->assertEquals(Money::EUR(100), $cart->discountTotal());
    }
}
