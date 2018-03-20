<?php

namespace Thinktomorrow\Trader\Tests\Discounts\Conditions;

use Money\Money;
use Thinktomorrow\Trader\Tests\TestCase;

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
}
