<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Cart\Discounts;

use Money\Money;

class MaximumAppliesTest
{
    /** @test */
    public function discount_is_applied_when_below_maximum_applies()
    {
        $this->setUpDatabase();
        $this->provideCartData();

        $product = ProductFactory::create(['slug:nl' => 'xxx', 'gross_amount' => 500])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(10)], [
            'MaximumRedemption' => new MaximumRedemption(3),
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
    public function discount_is_not_applied_when_maximum_applies_is_reached()
    {
        $this->setUpDatabase();
        $this->provideCartData();

        $customer = UserFactory::create()->getAsCustomer();
        auth()->login($customer);

        $product = ProductFactory::create(['slug:nl' => 'xxx', 'gross_amount' => 500])->withBrand()->toProductRead();
        $cart = CartFactory::createEmpty()->addProduct($product)->get();
        app(CurrentCart::class)->save($cart);

        $promo = PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(10)], [
            'MaximumRedemption' => new MaximumRedemption(1),
        ])->get();

        // Fake redeem this promo
        RedeemedPromoModel::create([
            'id' => 'xxx',
            'redeemer_id' => $customer->id,
            'promo_id' => $promo->getId()->get(),
            'order_id' => '123',
        ]);

        // Trigger the cart middleware to pick up the new version of the cart
        $this->setCookieOnResponse('optiphar-cart-rfr', 'xxx');

        $cart = app(CurrentCart::class)->get();

        $this->assertCount(0, $cart->discounts());
        $this->assertEquals(Money::EUR(500), $cart->total());
        $this->assertEquals(Money::EUR(500), $cart->subTotal());
        $this->assertEquals(Money::EUR(0), $cart->discountTotal());
    }
}