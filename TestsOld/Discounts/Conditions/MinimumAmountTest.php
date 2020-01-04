<?php

namespace Thinktomorrow\Trader\TestsOld\Discounts\Conditions;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\MinimumAmount;
use Thinktomorrow\Trader\TestsOld\TestCase;

class MinimumAmountTest extends TestCase
{
    /** @test */
    public function discount_with_minimum_amount_can_apply_if_subtotal_is_above_it()
    {
        list($order, $item) = $this->prepOrderWithItem(30);
        $discount = $this->makePercentageOffDiscount(15, ['minimum_amount' => Money::EUR(50)]);

        $this->assertFalse($discount->applicable($order, $item));

        $item->add(1);
        $this->assertTrue($discount->applicable($order, $item));
    }

    /** @test */
    public function it_can_set_parameters_from_raw_values()
    {
        // Current time falls out of given period
        $condition1 = (new MinimumAmount())->setParameters([
            'minimum_amount' => Money::EUR(50),
        ]);

        $condition2 = (new MinimumAmount())->setRawParameters([
            'minimum_amount' => 50,
        ]);

        // Parameter as set as single value
        $condition3 = (new MinimumAmount())->setRawParameters(50);

        $this->assertEquals($condition1, $condition2);
        $this->assertEquals($condition1, $condition3);
    }
}
