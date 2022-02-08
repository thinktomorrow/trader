<?php

namespace Thinktomorrow\Trader\Tests\Discounts;

use Money\Money;
use Optiphar\Cart\Http\CurrentCart;
use Optiphar\Promos\Common\Domain\Discount\DiscountPercentage;
use Optiphar\Promos\Common\Domain\Rules\ProductWhitelist;
use Tests\Factories\ProductFactory;
use Tests\Factories\PromoFactory;
use Tests\OptipharDatabaseTransactions;
use Tests\TestCase;
use Thinktomorrow\Trader\Tests\Cart\CartHelpers;

class AdjustCartDiscountBasePriceTest extends TestCase
{
    use OptipharDatabaseTransactions,
        CartHelpers;

    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /** @test */
    public function it_uses_the_cart_item_quantified_totals_to_calculate_cart_discounts_on()
    {
        $this->provideCartData();
        PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(20)]);

        $this->post(route('basket.items.add', [123456]), ['quantity' => 2, 'your_name' => '']);

        $cart = app(CurrentCart::class)->get();

        $this->assertEquals(1, $cart->discounts()->count());
        $this->assertEquals(2, $cart->quantity());
        $this->assertEquals(Money::EUR(180), $cart->discountTotal());
        $this->assertEquals(Money::EUR(360 + 360), $cart->total()); // 450 - 90 => 360 x 2 (and not based on 500 which is the non-sale price)
    }

    /** @test */
    public function it_does_not_rely_on_blacklisted_items()
    {
        $this->provideCartDataWithoutProducts();

        $product = ProductFactory::create(['gross_amount' => 500])->withBrand()->toProductRead();
        $product2 = ProductFactory::create(['gross_amount' => 300])->withBrand()->toProductRead();

        PromoFactory::createOrderPromo(['discount' => new DiscountPercentage(20)], ['ProductWhitelist' => new ProductWhitelist([$product->id], [], [])]);

        $this->post(route('basket.items.add', [$product->id]), ['quantity' => 2, 'your_name' => '']);
        $this->post(route('basket.items.add', [$product2->id]), ['quantity' => 2, 'your_name' => '']);

        $cart = app(CurrentCart::class)->get();

        $this->assertEquals(1, $cart->discounts()->count());

        $this->assertEquals(Money::EUR(200), $cart->discountTotal());
        $this->assertEquals(Money::EUR(1400), $cart->total());
    }
}
