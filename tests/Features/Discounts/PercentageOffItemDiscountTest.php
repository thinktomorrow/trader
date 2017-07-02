<?php

namespace Thinktomorrow\Trader\Tests\Features;

use Thinktomorrow\Trader\Discounts\Domain\Types\PercentageOffItemDiscount;
use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\DiscountConditions;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Price\Percentage;
use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePurchasable;
use Thinktomorrow\Trader\Tests\Unit\UnitTestCase;

class PercentageOffItemDiscountTest extends UnitTestCase
{
    /** @test */
    function it_can_apply_discount_to_items()
    {
        $discount = new PercentageOffItemDiscount(
            DiscountId::fromInteger(1),
            Percentage::fromPercent(20),
            new DiscountConditions([
                'purchasable_ids' => [20],
                'minimum_quantity' => 2,
                'maximum_affected_item_quantity' => 1 // how many items should benefit from this discount?
            ])
        );

        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(20,[],Money::EUR(110))),2);

        // Assert amount prior to item discount
        $this->assertEquals(Money::EUR(220), $order->total());

        // Apply discount
        $discount->apply($order);

        $this->assertEquals(Money::EUR(110)->multiply(0.2),$order->items()->find(20)->discountTotal());
        $this->assertEquals(Money::EUR(110)->multiply(0.8)->add(Money::EUR(110)), $order->total());

    }
}