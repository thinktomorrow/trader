<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\Types\FreeItemDiscount;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Domain\ItemId;
use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePurchasable;

class FreeItemTest extends UnitTestCase
{

    /** @test */
    function it_can_apply_discount()
    {
        $item = Item::fromPurchasable(new ConcretePurchasable(20,[],Money::EUR(100)));
        $item2 = Item::fromPurchasable(new ConcretePurchasable(22,[],Money::EUR(80)));

        $order = $this->makeOrder();
        $order->items()->add($item);

        $discount = new FreeItemDiscount(DiscountId::fromInteger(1),[],[
            'free_items' => [$item2]
        ]);

        $discount->apply($order);

        $this->assertCount(2,$order->items());
        $this->assertInstanceOf(AppliedDiscount::class,$order->discounts()[1]);
        $this->assertEquals(Money::EUR(100),$order->total());
    }
}