<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\Types\PercentageOffItemDiscount;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\ItemId;
use Thinktomorrow\Trader\Orders\Domain\PurchasableId;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;

class PercentageOffItemTest extends UnitTestCase
{
    /** @test */
    public function it_can_create_discount()
    {
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(20, [], Money::EUR(100))));

        $discount = new PercentageOffItemDiscount(DiscountId::fromInteger(1), [], [
            'percentage' => Percentage::fromPercent(10),
        ]);

        $discount->apply($order);

        $this->assertCount(1, $order->items()->find(PurchasableId::fromInteger(20))->discounts());
        $this->assertInstanceOf(AppliedDiscount::class, $order->items()->find(PurchasableId::fromInteger(20))->discounts()[1]);
    }

    /** @test */
    public function it_should_not_allow_to_go_below_ordered_subtotal()
    {
        $order = $this->makeOrder();
        $item = Item::fromPurchasable(new PurchasableStub(20, [], Money::EUR(100)));
        $order->items()->add($item, 2);

        $discount = new PercentageOffItemDiscount(DiscountId::fromInteger(1), [], [
            'percentage' => Percentage::fromPercent(110),
        ]);

        $discount->apply($order);

        $this->assertEquals(Money::EUR(0), $item->total());
        $this->assertEquals(Money::EUR(0), $order->total());
    }
}
