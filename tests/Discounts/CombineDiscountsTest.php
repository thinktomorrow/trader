<?php

namespace Thinktomorrow\Trader\Tests\Discounts;

use Money\Money;
use Thinktomorrow\Trader\Tests\TestCase;

class CombineDiscountsTest extends TestCase
{
    /** @test */
    public function discounts_can_be_combined_but_do_not_exceed_subtotal()
    {
        $order = $this->makeOrder(100);

        $discount = $this->makePercentageOffDiscount(80);
        $discount2 = $this->makePercentageOffDiscount(80);

        $discount->apply($order, $order);
        $discount2->apply($order, $order);

        $this->assertCount(2, $order->discounts());

        $this->assertEquals(Money::EUR(100), $order->subtotal());
        $this->assertEquals(Money::EUR(100), $order->discountTotal());

        // First applied discount
        $appliedDiscount = $order->discounts()[0];
        $this->assertEquals(\Thinktomorrow\Trader\Common\Price\Percentage::fromPercent(80), $appliedDiscount->discountPercentage());
        $this->assertEquals(Money::EUR(80), $appliedDiscount->discountAmount());
        $this->assertEquals(Money::EUR(100), $appliedDiscount->discountBasePrice());

        // Second applied discount - can only take up
        $appliedDiscount2 = $order->discounts()[1];
        $this->assertEquals(\Thinktomorrow\Trader\Common\Price\Percentage::fromPercent(20), $appliedDiscount2->discountPercentage());
        $this->assertEquals(Money::EUR(20), $appliedDiscount2->discountAmount());
        $this->assertEquals(Money::EUR(100), $appliedDiscount2->discountBasePrice());
    }

    /** @test */
    public function basket_discount_and_shipping_discount_can_be_combined()
    {
        $order = $this->makeOrder(100);
        $order->setShippingSubtotal(Money::EUR(200));

        $discount = $this->makePercentageOffDiscount(80);
        $discount2 = $this->makeShippingDiscount(80);

        $discount->apply($order, $order);
        $discount2->apply($order, $order->shippingCost());

        $this->assertCount(1, $order->discounts());
        $this->assertCount(1, $order->shippingDiscounts());

        $this->assertEquals(Money::EUR(100), $order->subtotal());
        $this->assertEquals(Money::EUR(80), $order->discountTotal());
        $this->assertEquals(Money::EUR(160), $order->shippingDiscountTotal());
        $this->assertEquals(Money::EUR(40), $order->shippingTotal());
        $this->assertEquals(Money::EUR(60), $order->total());

        // First applied discount
        $appliedDiscount = $order->discounts()[0];
        $this->assertEquals(\Thinktomorrow\Trader\Common\Price\Percentage::fromPercent(80), $appliedDiscount->discountPercentage());
        $this->assertEquals(Money::EUR(80), $appliedDiscount->discountAmount());
        $this->assertEquals(Money::EUR(100), $appliedDiscount->discountBasePrice());

        // Second applied discount - can only take up
        $appliedDiscount2 = $order->shippingDiscounts()[0];
        $this->assertEquals(\Thinktomorrow\Trader\Common\Price\Percentage::fromPercent(80), $appliedDiscount2->discountPercentage());
        $this->assertEquals(Money::EUR(160), $appliedDiscount2->discountAmount());
        $this->assertEquals(Money::EUR(200), $appliedDiscount2->discountBasePrice());
    }
}
