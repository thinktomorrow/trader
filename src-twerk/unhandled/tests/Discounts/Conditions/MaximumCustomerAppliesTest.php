<?php

namespace Thinktomorrow\Trader\Tests\Discounts\Conditions;

use Tests\TestCase;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\MaximumCustomerApplies;

class MaximumCustomerAppliesTest extends TestCase
{
    /** @test */
    public function discount_passes_when_customer_maximum_is_not_reached()
    {
        $condition = new MaximumCustomerApplies(2, 1);

        $order = $this->emptyOrder('xxx');

        $this->assertTrue($condition->check($order, $order));
    }

    /** @test */
    public function discount_does_not_pass_when_maximum_is_reached()
    {
        $condition = new MaximumCustomerApplies(2, 2);

        $order = $this->emptyOrder('xxx');

        $this->assertFalse($condition->check($order, $order));
    }
}
