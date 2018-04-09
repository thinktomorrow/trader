<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Common\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;

class AppliedDiscountTest extends TestCase
{
    use ShoppingHelpers;

    /** @test */
    public function it_can_create_applied_discount()
    {
        $discountId = DiscountId::fromInteger(1);
        $data = ['foobar'];
        $amount = Money::EUR(20);
        $discountBasePrice = Money::EUR(40);
        $percentage = Percentage::fromPercent(15);

        $appliedDiscount = new AppliedDiscount(
            $discountId,
            'foobar',
            $amount,
            $discountBasePrice,
            $percentage,
            $data
        );

        $this->assertSame($discountId, $appliedDiscount->discountId());
        $this->assertSame('foobar', $appliedDiscount->discountType());
        $this->assertSame($amount, $appliedDiscount->discountAmount());
        $this->assertSame($discountBasePrice, $appliedDiscount->discountBasePrice());
        $this->assertSame($percentage, $appliedDiscount->discountPercentage());
        $this->assertSame($data, $appliedDiscount->data());
    }

    /** @test */
    public function it_can_apply_fixed_amount_on_order()
    {
        $order = $this->makeOrder(100);
        $fixedAmountOff = $this->makeFixedAmountOffDiscount(40);
        $fixedAmountOff->apply($order, $order);

        $discounts = $order->discounts();
        $this->assertCount(1, $discounts);
        $appliedDiscount = reset($discounts);

        $this->assertEquals(Money::EUR(60), $order->total());
        $this->assertEquals(Money::EUR(40), $appliedDiscount->discountAmount());
        $this->assertEquals(Money::EUR(100), $appliedDiscount->discountBasePrice());
        $this->assertEquals(Percentage::fromPercent(40), $appliedDiscount->discountPercentage());
    }

    /** @test */
    public function applied_discount_has_reference_to_used_conditions()
    {
        $order = $this->makeOrder(100);
        $fixedAmountOff = $this->makeFixedAmountOffDiscount(40, ['minimum_amount' => Money::EUR(20)]);
        $fixedAmountOff->apply($order, $order);

        $discounts = $order->discounts();
        $appliedDiscount = reset($discounts);

        $this->assertCount(1, $appliedDiscount->data('conditions'));
        $this->assertEquals(20, $appliedDiscount->data('conditions.minimum_amount'));
    }
}
