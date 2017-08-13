<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\DiscountConditions;
use Thinktomorrow\Trader\Discounts\Domain\DiscountFactory;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Domain\ItemId;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Tests\InMemoryContainer;
use Thinktomorrow\Trader\Tests\Unit\Stubs\PurchasableStub;

class GeneralConditionsTest extends UnitTestCase
{
    /** @test */
    function by_default_discount_applies_for_entire_order()
    {
        $order = $this->makeOrder();
        $discount = $this->makePercentageOffDiscount();

        $this->assertTrue($discount->applicable($order));
    }

    /** @test */
    function discount_can_apply_for_specific_item()
    {
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(20,[],Money::EUR(110))),2);
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(21,[],Money::EUR(50))),1);

        $discount = (new DiscountFactory(new InMemoryContainer()))->create(1,'percentage_off_item',[
            'purchasable_ids' => [20]
        ],[
            'percentage' => Percentage::fromPercent(15),
        ]);

        $this->assertTrue($discount->applicable($order, ItemId::fromInteger(20)));
        $this->assertFalse($discount->applicable($order, ItemId::fromInteger(21)));
    }

    /** @test */
    function discount_cannot_apply_for_nonexisting_item()
    {
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(1,[],Money::EUR(110))),2);

        $discount = (new DiscountFactory(new InMemoryContainer()))->create(1,'percentage_off_item',[],[
            'percentage' => Percentage::fromPercent(15),
        ]);

        $this->assertFalse($discount->applicable($order, ItemId::fromInteger(2)));
    }

    /** @test */
    function discount_with_minimum_amount_can_apply_if_subtotal_is_above_it()
    {
        $order = $this->makeOrder();

        $discount = (new DiscountFactory(new InMemoryContainer()))->create(1,'percentage_off',[
            'minimum_amount' => Money::EUR(50)
        ],[
            'percentage' => Percentage::fromPercent(15),
        ]);

        $this->assertFalse($discount->applicable($order));

        // Add to subtotal
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(20,[],Money::EUR(100))));
        $this->assertTrue($discount->applicable($order));
    }

    /** @test */
    function discount_with_minimum_amount_can_apply_to_specific_items()
    {
        $order = $this->makeOrder();
        $item = Item::fromPurchasable(new PurchasableStub(20,[],Money::EUR(40)));

        $discount = (new DiscountFactory(new InMemoryContainer()))->create(1,'percentage_off_item',[
            'minimum_amount' => Money::EUR(50),
            'purchasable_ids' => [20]
        ],[
            'percentage' => Percentage::fromPercent(15),
        ]);

        $this->assertFalse($discount->applicable($order, $item->id()));

        // Add item to order
        $order->items()->add($item);
        $this->assertFalse($discount->applicable($order, $item->id()));

        $item->add(1);
        $this->assertTrue($discount->applicable($order, $item->id()));
    }

    /** @test */
    function it_can_be_applied_if_within_given_period()
    {
        $order = $this->makeOrder();

        $discount = (new DiscountFactory(new InMemoryContainer()))->create(1,'percentage_off',[
            'start_at' => (new \DateTime('@'.strtotime('+3 days' )))
        ],[
            'percentage' => Percentage::fromPercent(15),
        ]);

        $this->assertFalse($discount->applicable($order));

        // Period of discount has started
        $discount = (new DiscountFactory(new InMemoryContainer()))->create(1,'percentage_off',[
            'start_at' => (new \DateTime('@'.strtotime('-3 days' )))
        ],[
            'percentage' => Percentage::fromPercent(15),
        ]);

        $this->assertTrue($discount->applicable($order));

    }
}