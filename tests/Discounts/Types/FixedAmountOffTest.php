<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\Exceptions\CannotApplyDiscount;

class FixedAmountOffTest extends TestCase
{
    /** @test */
    public function fixed_amount_discount_cannot_be_negative()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makeFixedAmountOffDiscount(-10);
    }

    /** @test */
    public function fixed_amount_is_subtracted_from_original_price()
    {
        list($order, $item) = $this->prepOrderWithItem(100);
        $discount = $this->makeFixedAmountOffDiscount(15);

        $discount->apply($order, $order);

        $this->assertEquals(Money::EUR(100), $order->subtotal());
        $this->assertEquals(Money::EUR(100), $order->discountBasePrice());
        $this->assertEquals(Money::EUR(15), $order->discountTotal());
        $this->assertEquals(Money::EUR(85), $order->total());
        $this->assertCount(1, $order->discounts());
    }

    /** @test */
    public function for_item_discount_fixed_amount_is_subtracted_from_sale_price()
    {
        list($order, $item) = $this->prepOrderWithItem(100, 90);
        $discount = $this->makeFixedAmountOffDiscount(9);

        $discount->apply($order, $item);

        $this->assertCount(1, $item->discounts());
        $this->assertEquals(Money::EUR(90), $item->discountBasePrice());
        $this->assertEquals(Money::EUR(9), $item->discountTotal());

        $this->assertCount(0, $order->discounts());
        $this->assertEquals(Money::EUR(81), $order->subtotal());
        $this->assertEquals(Money::EUR(0), $order->discountTotal());
        $this->assertEquals(Money::EUR(81), $order->total());
    }

    /** @test */
    public function discount_cannot_be_higher_than_original_price()
    {
        $this->expectException(CannotApplyDiscount::class);

        list($order, $item) = $this->prepOrderWithItem(100);
        $discount = $this->makeFixedAmountOffDiscount(120);

        $discount->apply($order, $item);
    }

    /** @test */
    public function fixed_amount_discount_can_go_to_zero()
    {
        list($order, $item) = $this->prepOrderWithItem(100);
        $discount = $this->makeFixedAmountOffDiscount(0);

        $discount->apply($order, $item);

        $this->assertEquals(Money::EUR(100), $order->subtotal());
        $this->assertEquals(Money::EUR(100), $order->discountBasePrice());
        $this->assertEquals(Money::EUR(0), $order->discountTotal());
        $this->assertEquals(Money::EUR(100), $order->total());
    }
}
