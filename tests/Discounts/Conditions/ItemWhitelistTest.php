<?php

namespace Thinktomorrow\Trader\Tests\Discounts\Conditions;

use Money\Money;
use Thinktomorrow\Trader\Tests\TestCase;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\ItemWhitelist;

class ItemWhitelistTest extends TestCase
{
    /** @test */
    public function item_discount_passes_if_no_whitelist_is_enforced()
    {
        $condition = new ItemWhitelist();
        $item = $this->getItem(null, null, new PurchasableStub(20, [], Money::EUR(110)), 2);

        $this->assertTrue($condition->check($this->makeOrder(), $item));
    }

    /** @test */
    public function passed_value_must_be_an_array_of_ids()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ItemWhitelist())->setParameters(['item_whitelist' => 'foobar']);
    }

    /** @test */
    public function item_discount_passes_if_given_item_is_in_whitelist()
    {
        $order = $this->makeOrder();
        $item = $this->getItem(null, null, new PurchasableStub(20, [], Money::EUR(110)), 2);

        $condition = (new ItemWhitelist())->setParameters(['item_whitelist' => [20]]);
        $this->assertTrue($condition->check($order, $item));
    }

    /** @test */
    public function item_discount_does_not_pass_if_given_item_is_not_in_whitelist()
    {
        $order = $this->makeOrder();
        $item = $this->getItem(null, null, new PurchasableStub(20, [], Money::EUR(110)), 2);

        $condition = (new ItemWhitelist())->setParameters([5]);
        $this->assertFalse($condition->check($order, $item));
    }

    /** @test */
    public function order_discount_uses_whitelist_for_scoping_discount_baseprice()
    {
        list($order, $item) = $this->prepOrderWithItem(100);
        $item2 = $this->getItem(null,null,new PurchasableStub(20, [], Money::EUR(30)));
        $order->items()->add($item2);

        $discount = $this->makePercentageOffDiscount(50, ['item_whitelist' => [20]]);

        $discount->apply($order, $order);

        $this->assertEquals(Money::EUR(130), $order->subtotal());
        $this->assertEquals(Money::EUR(15), $order->discountTotal()); // 50% of discountBasePrice (which is 30)
        $this->assertEquals(Money::EUR(115), $order->total());
    }

    /** @test */
    public function it_can_set_parameters_from_raw_values()
    {
        // Current time falls out of given period
        $condition1 = (new ItemWhitelist())->setParameters([
            'item_whitelist' => [5, 10],
        ]);

        $condition2 = (new ItemWhitelist())->setParameterValues([
            'item_whitelist' => [5, 10],
        ]);

        $condition3 = (new ItemWhitelist())->setParameterValues([5, 10]);

        $this->assertEquals($condition1, $condition2);
        $this->assertEquals($condition1, $condition3);
    }
}
