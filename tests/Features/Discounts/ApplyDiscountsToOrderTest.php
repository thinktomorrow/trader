<?php

namespace Thinktomorrow\Trader\Tests\Features;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Application\ApplyDiscountsToOrder;
use Thinktomorrow\Trader\Discounts\Domain\DiscountCollection;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\Types\PercentageOffDiscount;
use Thinktomorrow\Trader\Discounts\Domain\Types\PercentageOffItemDiscount;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Tests\Unit\Stubs\PurchasableStub;
use Thinktomorrow\Trader\Tests\Unit\UnitTestCase;

class ApplyDiscountsToOrderTest extends UnitTestCase
{
    /** @test */
    public function it_can_apply_discounts_to_order()
    {
        // Set up order, items and discount
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(20, [], Money::EUR(240))));
        $percentageOffDiscount = new PercentageOffDiscount(DiscountId::fromInteger(1), [], ['percentage' => Percentage::fromPercent(20)]);

        // Apply discount to order
        (new ApplyDiscountsToOrder())->handle($order, new DiscountCollection([$percentageOffDiscount]));

        $this->assertCount(1, $order->discounts());
        $this->assertEquals(Money::EUR(240)->multiply(0.8), $order->total());
    }

    /** @test */
    public function it_can_apply_discount_to_items()
    {
        $discount = new PercentageOffItemDiscount(
            DiscountId::fromInteger(1), [],
            ['percentage' => Percentage::fromPercent(20)]
        );

        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(20, [], Money::EUR(110))), 2);
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(30, [], Money::EUR(240))));

        // Assert amount prior to item discount
        $this->assertEquals(Money::EUR(460), $order->total());

        // Apply discount
        $discount->apply($order);
    }
}
