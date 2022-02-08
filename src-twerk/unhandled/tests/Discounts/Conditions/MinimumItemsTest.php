<?php

namespace Thinktomorrow\Trader\Tests\Discounts\Conditions;

use Tests\TestCase;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\MinimumItems;

class MinimumItemsTest extends TestCase
{
    /** @test */
    public function condition_passes_if_no_minimum_is_enforced()
    {
        $condition = new MinimumItems(0, []);

        $order = $this->emptyOrder('xxx');

        $this->assertTrue($condition->check($order, $order));
    }

    /** @test */
    public function condition_passes_if_minimum_is_reached()
    {
        $condition = new MinimumItems(2, []);

        $order = $this->emptyOrder('xxx');
        $order->getItems()->addItem($this->defaultOrderProduct());
        $order->getItems()->addItem($this->defaultOrderProduct(['id' => "2"]));

        $this->assertTrue($condition->check($order, $order));
    }

    /** @test */
    public function condition_passes_if_quantity_of_same_item_reaches_minimum()
    {
        $condition = new MinimumItems(2, []);

        $order = $this->emptyOrder('xxx');
        $order->getItems()->addItem($this->defaultOrderProduct());
        $order->getItems()->addItem($this->defaultOrderProduct());

        $this->assertEquals(1, $order->getSize());
        $this->assertTrue($condition->check($order, $order));
    }

    /** @test */
    public function condition_does_not_pass_if_minimum_is_not_reached()
    {
        $condition = new MinimumItems(2, []);

        $order = $this->emptyOrder('xxx');
        $order->getItems()->addItem($this->defaultOrderProduct());

        $this->assertFalse($condition->check($order, $order));
    }

    /** @test */
    public function it_only_counts_quantity_for_items_that_pass_the_given_conditions()
    {
        $this->markTestSkipped();
    }
}
