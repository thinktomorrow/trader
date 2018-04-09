<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Common\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;

class DiscountTest extends TestCase
{
    /** @test */
    public function discountId_is_a_valid_identifier()
    {
        $discountId = DiscountId::fromInteger(2);

        $this->assertEquals(2, $discountId->get());
    }

    /** @test */
    public function it_applies_discount_to_order()
    {
        $order = $this->makeOrder(100);
        $discount = $this->makePercentageOffDiscount(20);

        $discount->apply($order, $order);

        $this->assertCount(1, $order->discounts());
        $this->assertEquals(Money::EUR(20), $order->discountTotal());
        $this->assertEquals(Money::EUR(80), $order->total());
    }

    /** @test */
    public function it_applies_discount_to_order_item()
    {
        $discount = $this->makePercentageOffDiscount(20);
        list($order, $item) = $this->prepOrderWithItem(100, 80);

        $discount->apply($order, $item);

        $this->assertCount(1, $item->discounts());
        $this->assertEquals(Money::EUR(16), $item->discountTotal());
        $this->assertEquals(Money::EUR(64), $item->total());

        $this->assertCount(0, $order->discounts());
        $this->assertEquals(Money::EUR(0), $order->discountTotal());
        $this->assertEquals(Money::EUR(64), $order->total());
    }

    /** @test */
    public function discount_cannot_go_below_order_subtotal()
    {
        $this->expectException(\InvalidArgumentException::class);

        $discount = $this->makePercentageOffDiscount(120);
        list($order, $item) = $this->prepOrderWithItem(50);

        $discount->apply($order, $order);
    }

    /** @test */
    public function discount_cannot_go_below_item_subtotal()
    {
        $this->expectException(\InvalidArgumentException::class);

        $discount = $this->makePercentageOffDiscount(120);
        list($order, $item) = $this->prepOrderWithItem(100);

        $discount->apply($order, $item);
    }

    /** @test */
    public function when_applied_an_applied_discount_is_kept_with_the_discount_amounts()
    {
        $discount = $this->makePercentageOffDiscount(20);
        list($order, $item) = $this->prepOrderWithItem(100);

        $discount->apply($order, $order);

        $appliedDiscount = $order->discounts()[0];
        $this->assertInstanceOf(AppliedDiscount::class, $appliedDiscount);

        $this->assertEquals(Money::EUR(20), $appliedDiscount->discountAmount());
        $this->assertEquals(Percentage::fromPercent(20), $appliedDiscount->discountPercentage());
        $this->assertEquals('percentage_off', $appliedDiscount->discountType());
        $this->assertInstanceof(DiscountId::class, $appliedDiscount->discountId());
    }

    /** @test */
    public function when_applied_an_applied_discount_is_kept_with_custom_data()
    {
        $discount = $this->makePercentageOffDiscount(20, [], ['foo' => 'bar']);
        list($order, $item) = $this->prepOrderWithItem(100);

        $discount->apply($order, $item);

        $appliedDiscount = $item->discounts()[0];
        $this->assertEquals(['foo' => 'bar'], $appliedDiscount->data());
        $this->assertEquals('bar', $appliedDiscount->data('foo'));
    }

    /** @test */
    public function you_can_check_if_discount_uses_certain_condition()
    {
        $discount = $this->makePercentageOffDiscount(15, ['minimum_amount' => Money::EUR(50)]);

        $this->assertTrue($discount->usesCondition('minimum_amount'));
        $this->assertFalse($discount->usesCondition('item_blacklist'));
    }
}
