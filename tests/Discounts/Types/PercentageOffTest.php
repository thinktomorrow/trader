<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Common\Adjusters\Amount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\Types\PercentageOffDiscount;

class PercentageOffTest extends TestCase
{
    /** @test */
    public function percentage_off_discount_cannot_be_negative()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makePercentageOffDiscount(-10);
    }

    /** @test */
    public function percentage_off_is_subtracted_from_original_price()
    {
        list($order, $item) = $this->prepOrderWithItem(100);
        $discount = $this->makePercentageOffDiscount(40);

        $discount->apply($order, $order);

        $this->assertEquals(Money::EUR(100), $order->subtotal());
        $this->assertEquals(Money::EUR(100), $order->discountBasePrice());
        $this->assertEquals(Money::EUR(40), $order->discountTotal());
        $this->assertEquals(Money::EUR(60), $order->total());
        $this->assertCount(1, $order->discounts());
    }

    /** @test */
    public function for_item_discount_percentage_off_is_subtracted_from_sale_price()
    {
        list($order, $item) = $this->prepOrderWithItem(100, 90);
        $discount = $this->makePercentageOffDiscount(10);

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
        $this->expectException(\InvalidArgumentException::class);

        list($order, $item) = $this->prepOrderWithItem(100);
        $discount = $this->makePercentageOffDiscount(120);

        $discount->apply($order, $item);
    }

    /** @test */
    public function percentage_off_discount_can_go_to_zero()
    {
        list($order, $item) = $this->prepOrderWithItem(100);
        $discount = $this->makePercentageOffDiscount(0);

        $discount->apply($order, $item);

        $this->assertEquals(Money::EUR(100), $order->subtotal());
        $this->assertEquals(Money::EUR(100), $order->discountBasePrice());
        $this->assertEquals(Money::EUR(0), $order->discountTotal());
        $this->assertEquals(Money::EUR(100), $order->total());
    }

    /** @test */
    public function it_requires_a_percentage_adjuster()
    {
        $this->expectException(\InvalidArgumentException::class);

        new PercentageOffDiscount(
            DiscountId::fromInteger(1),
            [],
            (new Amount())->setParameters(Money::EUR(10)),
            []
        );
    }
}
