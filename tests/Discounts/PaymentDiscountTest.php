<?php

namespace Thinktomorrow\Trader\Tests\Discounts;

use Money\Money;
use Thinktomorrow\Trader\Tests\TestCase;

class PaymentDiscountTest extends TestCase
{
    /** @test */
    public function percentage_off_payment_discount_can_be_higher_than_subtotal_when_payment_total_is_higher()
    {
        $order = $this->makeOrder(100);
        $order->setPaymentSubtotal(Money::EUR(200));

        $discount = $this->makePaymentDiscount(90);

        $discount->apply($order, $order->paymentCost());

        $this->assertCount(1, $order->paymentDiscounts());

        // Order basket discounts are not affected by payment discounts
        $this->assertCount(0, $order->discounts());
        $this->assertEquals(Money::EUR(0), $order->discountTotal());

        $appliedDiscount = $order->paymentDiscounts()[0];
        $this->assertEquals(Money::EUR(100), $order->subtotal());
        $this->assertEquals(Money::EUR(180), $order->paymentDiscountTotal());
        $this->assertEquals(Money::EUR(180), $order->paymentDiscountTotal());
        $this->assertEquals(Money::EUR(20), $order->paymentTotal());
        $this->assertEquals(Money::EUR(120), $order->total());

        $this->assertEquals(\Thinktomorrow\Trader\Common\Price\Percentage::fromPercent(90), $appliedDiscount->discountPercentage());
        $this->assertEquals(Money::EUR(180), $appliedDiscount->discountAmount());
        $this->assertEquals(Money::EUR(200), $appliedDiscount->discountBasePrice());
    }

    /** @test */
    public function percentage_off_payment_discount_can_be_combined_with_other_payment_discount()
    {
        $order = $this->makeOrder(100);
        $order->setPaymentSubtotal(Money::EUR(200));

        $discount = $this->makePaymentDiscount(90);
        $discount2 = $this->makePaymentDiscount(90);

        $discount->apply($order, $order->paymentCost());
        $discount2->apply($order, $order->paymentCost());

        $this->assertCount(2, $order->paymentDiscounts());
        $this->assertCount(0, $order->discounts());

        $this->assertEquals(Money::EUR(100), $order->subtotal());
        $this->assertEquals(Money::EUR(200), $order->paymentDiscountTotal());
        $this->assertEquals(Money::EUR(200), $order->paymentSubtotal());
        $this->assertEquals(Money::EUR(0), $order->paymentTotal());
        $this->assertEquals(Money::EUR(100), $order->total());

        // First applied discount
        $appliedDiscount = $order->paymentDiscounts()[0];
        $this->assertEquals(\Thinktomorrow\Trader\Common\Price\Percentage::fromPercent(90), $appliedDiscount->discountPercentage());
        $this->assertEquals(Money::EUR(180), $appliedDiscount->discountAmount());
        $this->assertEquals(Money::EUR(200), $appliedDiscount->discountBasePrice());

        // Second applied discount - can only take up as much as remains available
        $appliedDiscount2 = $order->paymentDiscounts()[1];
        $this->assertEquals(\Thinktomorrow\Trader\Common\Price\Percentage::fromPercent(10), $appliedDiscount2->discountPercentage());
        $this->assertEquals(Money::EUR(20), $appliedDiscount2->discountAmount());
        $this->assertEquals(Money::EUR(200), $appliedDiscount2->discountBasePrice());
    }
}
