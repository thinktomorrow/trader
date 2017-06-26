<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\DiscountConditions;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePurchasable;

class DiscountConditionsTest extends UnitTestCase
{
    /** @test */
    function by_default_discount_applies_for_entire_order()
    {
        $order = new Order();
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(20,[],Money::EUR(110))),2);

        $conditions = new DiscountConditions([]);
        $this->assertTrue($conditions->applicableToOrder($order));
        $this->assertFalse($conditions->applicableToItem($order, 20));
    }

    /** @test */
    function discount_can_apply_for_specific_items()
    {
        $order = new Order();
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(20,[],Money::EUR(110))),2);

        $conditions = new DiscountConditions([
            'applies_to' => 'item',
            'purchasable_ids' => [20]
        ]);

        $this->assertFalse($conditions->applicableToOrder($order));
        $this->assertTrue($conditions->applicableToItem($order, 20));
    }

    /** @test */
    function discount_can_apply_explicitly_to_order_even_with_given_purchasable_ids()
    {
        $order = new Order();
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(20,[],Money::EUR(110))),2);

        $conditions = new DiscountConditions([
            'applies_to' => 'order',
            'purchasable_ids' => [20]
        ]);
        $this->assertTrue($conditions->applicableToOrder($order));
        $this->assertFalse($conditions->applicableToItem($order, 20));
    }

    /** @test */
    function discount_cannot_apply_for_nonexisting_item()
    {
        $order = new Order();

        $conditions = new DiscountConditions([
            'applies_to' => 'item',
            'purchasable_ids' => [20]
        ]);

        $this->assertFalse($conditions->applicableToItem($order, 1));
    }

    /** @test */
    function it_can_apply_if_subtotal_is_above_minimum_amount()
    {
        $order = new Order;

        $conditions = new DiscountConditions([
            'minimum_amount' => Money::EUR(50)
        ]);

        $this->assertFalse($conditions->applicableToOrder($order));

        // Add to subtotal
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(20,[],Money::EUR(100))));
        $this->assertTrue($conditions->applicableToOrder($order));
    }

    /** @test */
    function it_can_apply_if_item_subtotal_is_above_minimum_amount()
    {
        $order = new Order;

        $conditions = new DiscountConditions([
            'applies_to' => 'item',
            'purchasable_ids' => [20],
            'minimum_amount' => Money::EUR(50)
        ]);

        $this->assertFalse($conditions->applicableToItem($order, 20));

        $item = Item::fromPurchasable(new ConcretePurchasable(20,[],Money::EUR(40)));

        // Add to subtotal
        $order->items()->add($item);
        $this->assertFalse($conditions->applicableToItem($order, 20));

        $item->add(1);
        $this->assertTrue($conditions->applicableToItem($order,20));
    }

    /** @test */
    function it_can_be_applied_if_within_given_period()
    {
        $order = new Order;

        // Period of discount has not started yet
        $conditions = new DiscountConditions([
            'applies_to' => 'order',
            'start_at' => (new \DateTime('@'.strtotime('+3 days' )))
        ]);

        $this->assertFalse($conditions->applicableToOrder($order));

        // Period of discount has started
        $conditions = new DiscountConditions([
            'applies_to' => 'order',
            'start_at' => (new \DateTime('@'.strtotime('-3 days')))
        ]);

        $this->assertTrue($conditions->applicableToOrder($order));

    }
}