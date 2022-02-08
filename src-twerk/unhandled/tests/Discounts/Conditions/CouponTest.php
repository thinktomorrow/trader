<?php

namespace Thinktomorrow\Trader\Tests\Discounts\Conditions;

use Tests\TestCase;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\Coupon;

class CouponTest extends TestCase
{
    /** @test */
    public function coupon_passes_if_cart_has_matching_code()
    {
        $condition = new Coupon('foobar');

        $order = $this->emptyOrder('xxx');
        $order->enterCoupon('foobar');

        $this->assertEquals('foobar', $order->getCoupon());
        $this->assertTrue($condition->check($order, $order));
    }

    /** @test */
    public function coupon_code_is_case_insensitive()
    {
        $condition = new Coupon('foobar');

        $order = $this->emptyOrder('xxx');
        $order->enterCoupon('FOOBAR');

        $this->assertEquals('FOOBAR', $order->getCoupon());
        $this->assertTrue($condition->check($order, $order));
    }
}
