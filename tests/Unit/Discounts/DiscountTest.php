<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;

class DiscountTest extends UnitTestCase
{
    /** @test */
    function discountId_is_a_valid_identifier()
    {
        $discountId = DiscountId::fromInteger(2);

        $this->assertEquals(2,$discountId->get());
    }

    /** @test */
    function it_applies_discount_to_order()
    {
        $order = $this->makeOrder(100);
        $discount = $this->makePercentageOffDiscount(20);

        $discount->apply($order);

        $this->assertCount(1,$order->discounts());
        $this->assertEquals(Money::EUR(80),$order->total());
    }

    /** @test */
    function discount_cannot_go_below_order_subtotal()
    {
        $order = $this->makeOrder(50);
        $discount = $this->makePercentageOffDiscount(120);

        $discount->apply($order);

        $this->assertEquals(Money::EUR(0), $order->total());
    }
}