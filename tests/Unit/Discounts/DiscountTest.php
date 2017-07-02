<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountFactory;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Price\Percentage;
use Thinktomorrow\Trader\Tests\DummyContainer;
use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePercentageOffDiscount;
use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePurchasable;

class DiscountTest extends UnitTestCase
{
    /** @test */
    function it_can_add_applied_discount_to_order()
    {
        $order = $this->makeOrder();
        $discount = (new DiscountFactory(new DummyContainer()))->create(1,'percentage_off',[],[
            'percentage' => Percentage::fromPercent(20),
        ]);

        $discount->apply($order);

        $this->assertCount(1,$order->discounts());
    }

    /** @test */
    function adding_applied_discount_straight_to_order_will_not_affect_totalprice()
    {
        $order = $this->makeOrder();
        $discount = (new DiscountFactory(new DummyContainer()))->create(1,'percentage_off',[],[
            'percentage' => Percentage::fromPercent(20),
        ]);

        $discount->apply($order);

        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(20,[],Money::EUR(240))));
        $this->assertEquals(Money::EUR(240), $order->total());

        // Applying via domain will NOT change order and totals
        $this->assertEquals(Money::EUR(240), $order->total());
    }
}