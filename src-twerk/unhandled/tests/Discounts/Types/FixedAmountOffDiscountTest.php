<?php

namespace Thinktomorrow\Trader\Tests\Discounts\Types;

use Money\Money;
use Optiphar\Cart\Http\CurrentCart;
use Optiphar\Discounts\DiscountId;
use Optiphar\Discounts\Types\FixedAmountOffDiscount;
use Optiphar\Promos\Common\Domain\Discount\DiscountAmount;
use Optiphar\Promos\Common\Domain\Rules\MinimumAmount;
use Tests\Factories\CartFactory;
use Tests\Factories\ProductFactory;
use Tests\Factories\PromoFactory;
use Tests\OptipharDatabaseTransactions;
use Tests\TestCase;
use Thinktomorrow\Trader\Tests\Cart\CartHelpers;

class FixedAmountOffDiscountTest extends TestCase
{
    use OptipharDatabaseTransactions, CartHelpers;

    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->provideCartData();
    }

    /** @test */
    public function fixed_amount_discount_cannot_be_negative()
    {
        $this->expectException(\InvalidArgumentException::class);

        new FixedAmountOffDiscount(DiscountId::fromString('xxx'), Money::EUR(-10), [], []);
    }

    /** @test */
    public function discount_cannot_exceed_subtotal()
    {
        $product = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();

        $discount = new FixedAmountOffDiscount(DiscountId::fromString('xxx'), Money::EUR(150), [], []);
        $discount->apply($cart);

        $this->assertCount(1, $cart->discounts());
        $this->assertEquals(Money::EUR(0), $cart->total());
        $this->assertEquals(Money::EUR(100), $cart->discountTotal());
    }

    /** @test */
    public function fixed_amount_is_subtracted_from_original_price()
    {
        $product = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo([
            'discount' => new DiscountAmount(Money::EUR(30)),
            'minimumAmount' => MinimumAmount::fromEUR(50),
        ], []);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertCount(1, $cart->discounts());
        $this->assertEquals(Money::EUR(70), $cart->total());
        $this->assertEquals(Money::EUR(30), $cart->discountTotal());
    }

    /** @test */
    public function discount_can_reduce_total_to_zeror()
    {
        $product = ProductFactory::create(['gross_amount' => 100])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo([
            'discount' => new DiscountAmount(Money::EUR(100)),
            'minimumAmount' => MinimumAmount::fromEUR(100),
        ], []);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertCount(1, $cart->discounts());
        $this->assertEquals(Money::EUR(0), $cart->total());
        $this->assertEquals(Money::EUR(100), $cart->discountTotal());
    }
}
