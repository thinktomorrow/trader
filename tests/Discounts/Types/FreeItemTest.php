<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\Types\FreeItemDiscount;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;

class FreeItemTest extends UnitTestCase
{
    /** @test */
    public function it_can_apply_discount()
    {
        $item = Item::fromPurchasable(new PurchasableStub(20, [], Money::EUR(100)));
        $item2 = Item::fromPurchasable(new PurchasableStub(22, [], Money::EUR(80)));

        $order = $this->makeOrder();
        $order->items()->add($item);

        $discount = new FreeItemDiscount(DiscountId::fromInteger(1), [], [
            'free_items' => [$item2],
        ]);

        $discount->apply($order);

        $this->assertCount(2, $order->items());
        $this->assertInstanceOf(AppliedDiscount::class, $order->discounts()[1]);
        $this->assertEquals(Money::EUR(100), $order->total());
    }
}
