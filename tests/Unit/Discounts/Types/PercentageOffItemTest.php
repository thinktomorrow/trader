<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\MinimumAmount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\Types\PercentageOffItemDiscount;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Domain\ItemId;
use Thinktomorrow\Trader\Price\Percentage;
use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePurchasable;

class PercentageOffItemTest extends UnitTestCase
{

    /** @test */
    function it_can_create_discount()
    {
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(20,[],Money::EUR(100))));

        $discount = new PercentageOffItemDiscount(DiscountId::fromInteger(1),[],[
            'percentage' => Percentage::fromPercent(10)
        ]);

        $discount->apply($order);

        $this->assertCount(1,$order->items()->find(ItemId::fromInteger(20))->discounts());
        $this->assertInstanceOf(AppliedDiscount::class,$order->items()->find(ItemId::fromInteger(20))->discounts()[1]);
    }

    /** @test */
    function it_should_not_allow_to_go_below_ordered_subtotal()
    {
        $order = $this->makeOrder();
        $item = Item::fromPurchasable(new ConcretePurchasable(20,[],Money::EUR(100)));
        $order->items()->add($item,2);

        $discount = new PercentageOffItemDiscount(DiscountId::fromInteger(1),[],[
            'percentage' => Percentage::fromPercent(110)
        ]);

        $discount->apply($order);

        $this->assertEquals(Money::EUR(0), $item->total());
        $this->assertEquals(Money::EUR(0),$order->total());

    }
}