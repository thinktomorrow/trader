<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\MinimumAmount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\Types\PercentageOffDiscount;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;

class PercentageOffTest extends TestCase
{
    // TODO MAKE GENERAL TESTCASE SO EACH DISCOUNTTYPE CAN USE IT

    /** @test */
    public function it_can_create_discount()
    {
        $order = $this->makeOrder();

        $discount = new PercentageOffDiscount(DiscountId::fromInteger(1), [
            new MinimumAmount(),
        ], [
            'percentage' => Percentage::fromPercent(10),
        ]);

        $discount->apply($order);

        $this->assertCount(1, $order->discounts());
        $this->assertInstanceOf(AppliedDiscount::class, $order->discounts()[1]);
    }

    /** @test */
    public function it_should_not_allow_to_go_below_ordered_subtotal()
    {
        $order = $this->makeOrder();
        $order->items()->add($this->getItem(null, null, new PurchasableStub(20, [], Money::EUR(100))));

        $discount = new PercentageOffDiscount(DiscountId::fromInteger(1), [], [
            'percentage' => Percentage::fromPercent(110),
        ]);

        $discount->apply($order);

        $this->assertEquals(Money::EUR(0), $order->total());
    }
}
