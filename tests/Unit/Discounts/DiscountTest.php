<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Price\Percentage;
use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePercentageOffDiscount;
use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePurchasable;

class DiscountTest extends UnitTestCase
{
    /** @test */
    function it_can_add_applied_discount_to_order()
    {
        $order = new Order();
        $percentageOffDiscount = new ConcretePercentageOffDiscount(1,Percentage::fromPercent(20));
        $appliedDiscount = $percentageOffDiscount->apply($order);

        $order->addDiscount($appliedDiscount);

        $this->assertCount(1,$order->discounts());

    }

    /** @test */
    function directly_adding_applied_discount_to_order_will_not_change_totalprice()
    {
        $order = new Order();
        $percentageOffDiscount = new ConcretePercentageOffDiscount(1,Percentage::fromPercent(20));
        $appliedDiscount = $percentageOffDiscount->apply($order);

        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(20,[],Money::EUR(240))));
        $this->assertEquals(Money::EUR(240), $order->total());

        $order->addDiscount($appliedDiscount);

        // Applying via domain will NOT change order and totals
        $this->assertEquals(Money::EUR(240), $order->total());
    }
}