<?php

namespace Thinktomorrow\Trader\Tests\Discounts;

use Money\Money;
use Thinktomorrow\Trader\Tests\TestCase;

class ShippingDiscountTest extends TestCase
{
    /** @test */
    public function percentage_off_shipping_discount_can_be_higher_than_subtotal_when_shipping_total_is_higher()
    {
        $order = $this->makeOrder(100);
        $order->setShippingSubtotal(Money::EUR(200));

        $discount = $this->makeShippingDiscount(90);

        $discount->apply($order, $order->shippingCost());

        $this->assertCount(1, $order->shippingDiscounts());

        // Order basket discounts are not affected by shipping discounts
        $this->assertCount(0, $order->discounts());
        $this->assertEquals(Money::EUR(0), $order->discountTotal());

        $appliedDiscount = $order->shippingDiscounts()[0];
        $this->assertEquals(Money::EUR(100), $order->subtotal());
        $this->assertEquals(Money::EUR(180), $order->shippingDiscountTotal());
        $this->assertEquals(Money::EUR(180), $order->shippingDiscountTotal());
        $this->assertEquals(Money::EUR(20), $order->shippingTotal());
        $this->assertEquals(Money::EUR(120), $order->total());

        $this->assertEquals(\Thinktomorrow\Trader\Common\Price\Percentage::fromPercent(90), $appliedDiscount->discountPercentage());
        $this->assertEquals(Money::EUR(180), $appliedDiscount->discountAmount());
        $this->assertEquals(Money::EUR(200), $appliedDiscount->discountBasePrice());
    }

    /** @test */
    public function percentage_off_shipping_discount_can_be_combined_with_other_shipping_discount()
    {
        $order = $this->makeOrder(100);
        $order->setShippingSubtotal(Money::EUR(200));

        $discount = $this->makeShippingDiscount(90);
        $discount2 = $this->makeShippingDiscount(90);

        $discount->apply($order, $order->shippingCost());
        $discount2->apply($order, $order->shippingCost());

        $this->assertCount(2, $order->shippingDiscounts());
        $this->assertCount(0, $order->discounts());

        $this->assertEquals(Money::EUR(100), $order->subtotal());
        $this->assertEquals(Money::EUR(200), $order->shippingDiscountTotal());
        $this->assertEquals(Money::EUR(200), $order->shippingSubtotal());
        $this->assertEquals(Money::EUR(0), $order->shippingTotal());
        $this->assertEquals(Money::EUR(100), $order->total());

        // First applied discount
        $appliedDiscount = $order->shippingDiscounts()[0];
        $this->assertEquals(\Thinktomorrow\Trader\Common\Price\Percentage::fromPercent(90), $appliedDiscount->discountPercentage());
        $this->assertEquals(Money::EUR(180), $appliedDiscount->discountAmount());
        $this->assertEquals(Money::EUR(200), $appliedDiscount->discountBasePrice());

        // Second applied discount - can only take up as much as remains available
        $appliedDiscount2 = $order->shippingDiscounts()[1];
        $this->assertEquals(\Thinktomorrow\Trader\Common\Price\Percentage::fromPercent(10), $appliedDiscount2->discountPercentage());
        $this->assertEquals(Money::EUR(20), $appliedDiscount2->discountAmount());
        $this->assertEquals(Money::EUR(200), $appliedDiscount2->discountBasePrice());
    }
}
