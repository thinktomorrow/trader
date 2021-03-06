<?php

namespace Thinktomorrow\Trader\Tests\Discounts\Conditions;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\ItemBlacklist;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;
use Thinktomorrow\Trader\Tests\TestCase;

class ItemBlacklistTest extends TestCase
{
    /** @test */
    public function it_passes_if_no_blacklist_is_enforced()
    {
        $condition = new ItemBlacklist();
        $item = $this->getItem(null, null, new PurchasableStub(20, [], Money::EUR(110)), 2);

        $this->assertTrue($condition->check($this->makeOrder(), $item));
    }

    /** @test */
    public function passed_value_must_be_an_array_of_ids()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ItemBlacklist())->setParameters(['item_blacklist' => 'foobar']);
    }

    /** @test */
    public function it_passes_if_given_item_is_not_in_blacklist()
    {
        $order = $this->makeOrder();
        $item = $this->getItem(null, null, new PurchasableStub(20, [], Money::EUR(110)), 2);

        $condition = (new ItemBlacklist())->setParameters(['item_blacklist' => [5]]);
        $this->assertTrue($condition->check($order, $item));
    }

    /** @test */
    public function it_does_not_pass_if_given_item_is_in_blacklist()
    {
        $order = $this->makeOrder();
        $item = $this->getItem(null, null, new PurchasableStub(20, [], Money::EUR(110)), 2);

        $condition = (new ItemBlacklist())->setParameters(['item_blacklist' => [20]]);
        $this->assertFalse($condition->check($order, $item));
    }

    /** @test */
    public function parameter_can_be_passed_as_non_assoc_array_as_well()
    {
        $order = $this->makeOrder();
        $item = $this->getItem(null, null, new PurchasableStub(5, [], Money::EUR(110)), 2);

        $condition = (new ItemBlacklist())->setParameters([5]);
        $this->assertFalse($condition->check($order, $item));
    }

    /** @test */
    public function order_discount_uses_blacklist_for_scoping_discount_baseprice()
    {
        list($order, $item) = $this->prepOrderWithItem(100);
        $item2 = $this->getItem(null, null, new PurchasableStub(20, [], Money::EUR(30)));
        $order->items()->add($item2);

        $discount = $this->makePercentageOffDiscount(50, ['item_blacklist' => [20]]);

        $discount->apply($order, $order);

        $this->assertEquals(Money::EUR(130), $order->subtotal());
        $this->assertEquals(Money::EUR(50), $order->discountTotal()); // Only first item is accepted so 50% of 100
        $this->assertEquals(Money::EUR(80), $order->total());
    }

    /** @test */
    public function it_can_set_parameters_from_raw_values()
    {
        // Current time falls out of given period
        $condition1 = (new ItemBlacklist())->setParameters([
            'item_blacklist' => [5, 10],
        ]);

        $condition2 = (new ItemBlacklist())->setRawParameters([
            'item_blacklist' => [5, 10],
        ]);

        $condition3 = (new ItemBlacklist())->setRawParameters([5, 10]);

        $this->assertEquals($condition1, $condition2);
        $this->assertEquals($condition1, $condition3);
    }
}
