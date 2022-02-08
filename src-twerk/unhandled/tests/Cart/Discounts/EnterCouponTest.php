<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Discounts;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\Coupon;

class EnterCouponTest
{
    /** @test */
    public function coupon_does_not_pass_if_cart_does_not_have_matching_code()
    {
        $condition = new Coupon('foobar');

        $order = $this->storeOrder('xxx');
        $order->enterCoupon('invalid coupon');

        $this->assertEquals('invalid coupon', $order->getCoupon());
        $this->assertFalse($condition->check($order, $order));
    }

    /** @test */
    public function coupon_is_applied_if_cart_has_matching_code()
    {
        $this->setUpDatabase();
        $this->provideCartData();

        $product = ProductFactory::create(['slug:nl' => 'xxx', 'gross_amount' => 500])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty('xxx', ['details' => [
            'coupon' => 'foobar',
        ]])->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createCouponPromo([
            'discount' => new DiscountPercentage(10),
        ], [], [], []);

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
    public function coupon_is_not_applied_if_cart_does_not_have_matching_code()
    {
        $this->setUpDatabase();
        $this->provideCartData();

        $product = ProductFactory::create(['slug:nl' => 'xxx', 'gross_amount' => 500])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty('xxx', ['details' => [
            'coupon' => 'FAKE',
        ]])->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createCouponPromo([
            'discount' => new DiscountPercentage(10),
        ], [], [], []);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');

        $cart = app(CurrentCart::class)->get();

        $this->assertCount(0, $cart->discounts());
        $this->assertEquals(Money::EUR(500), $cart->total());
        $this->assertEquals(Money::EUR(500), $cart->subTotal());
        $this->assertEquals(Money::EUR(0), $cart->discountTotal());
    }

    /** @test */
    public function a_coupon_promo_can_be_combined_with_a_free_product_discount()
    {
        $this->setUpDatabase();
        $this->provideCartData();

        $product = ProductFactory::create()->withBrand()->toProductRead();
        $freeProduct = ProductFactory::create()->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty('xxx', ['details' => [
            'coupon' => 'foobar',
        ]])->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        $promo = PromoFactory::createCouponPromo(['discount' => new DiscountPercentage(40)], [
            'IsCombinable' => new IsCombinable(),
        ]);
        $promo2 = PromoFactory::createOrderPromo(['discount' => new FreeProductDiscount($freeProduct->id)], [
            'IsCombinable' => new IsCombinable(),
        ]);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertCount(2, $cart->items());
        $this->assertEquals(Money::EUR(200 * 0.6), $cart->total()); // 40% discount
    }

    /** @test */
    public function when_coupon_promo_is_valid_this_has_priority_over_any_order_promo()
    {
        $this->setUpDatabase();
        $this->provideCartData();

        $product = ProductFactory::create()->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty('xxx', ['details' => [
            'coupon' => 'foobar',
        ]])->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createCouponPromo([
            'discount' => new DiscountPercentage(40),
        ], [], [], []);
        PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(80)])->get();

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertEquals('foobar', $cart->enteredCoupon());
        $this->assertEquals(Money::EUR(120), $cart->total()); // 40 % discount from coupon code
    }

    /** @test */
    public function it_cannot_accumulate_order_promos_and_it_takes_the_highest_discount()
    {
        $this->setUpDatabase();
        $this->provideCartData();

        $product = ProductFactory::create()->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty('xxx', ['details' => [
            'coupon' => 'foobar',
        ]])->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        $promo = PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(40)])->get();
        $promo2 = PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(80)])->get();

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertNotNull($cart->enteredCoupon());
        $this->assertEquals(Money::EUR(40), $cart->total()); // 80 % discount
    }

    /** @test */
    public function when_coupon_promo_is_invalid_the_general_order_promo_is_still_applied()
    {
        $this->setUpDatabase();
        $this->provideCartData();

        $product = ProductFactory::create()->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty('xxx', ['details' => [
            'coupon' => 'FAKE',
        ]])->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        $promo = PromoFactory::createCouponPromo(['discount' => new DiscountPercentage(40)])->get();
        $promo2 = PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(30)])->get();

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');
        $cart = app(CurrentCart::class)->get();

        $this->assertCount(1, $cart->discounts());
        $this->assertEquals(Money::EUR(140), $cart->total()); // 30 % discount from order promo
    }
}
