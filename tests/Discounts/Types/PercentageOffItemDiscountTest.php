<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\DiscountFactory;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\PurchasableId;
use Thinktomorrow\Trader\Tests\InMemoryContainer;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;

class PercentageOffItemDiscountTest extends UnitTestCase
{
    /** @test */
    public function it_can_apply_discount_to_items()
    {
        $discount = (new DiscountFactory(new InMemoryContainer()))->create(1, 'percentage_off_item', [
            'purchasable_ids'       => [20],
            'minimum_item_quantity' => 2,
        ], [
            'maximum_affected_quantity' => 1,
            'percentage'                => Percentage::fromPercent(20),
        ]);

        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(20, [], Money::EUR(110))), 2);

        // Assert amount prior to item discount
        $this->assertEquals(Money::EUR(220), $order->total());

        // Apply discount
        $discount->apply($order);

        $this->assertEquals(Money::EUR(110)->multiply(0.2), $order->items()->find(PurchasableId::fromInteger(20))->discountTotal());
        $this->assertEquals(Money::EUR(110)->multiply(0.8)->add(Money::EUR(110)), $order->total());
    }

    /** @test */
    function it_can_()
    {
        $this->markTestIncomplete('Add tests for each discount type');
    }
}
