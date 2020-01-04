<?php

namespace Thinktomorrow\Trader\TestsOld\Shipment\Conditions;

use Money\Money;
use Thinktomorrow\Trader\Shipment\Domain\Conditions\MaximumAmount;
use Thinktomorrow\Trader\TestsOld\TestCase;

class MaximumAmountTest extends TestCase
{
    /** @test */
    public function it_can_set_parameters_from_raw_values()
    {
        // Current time falls out of given period
        $condition1 = (new MaximumAmount())->setParameters([
            'maximum_amount' => Money::EUR(50),
        ]);

        $condition2 = (new MaximumAmount())->setRawParameters([
            'maximum_amount' => 50,
        ]);

        // Parameter as set as single value
        $condition3 = (new MaximumAmount())->setRawParameters(50);

        $this->assertEquals($condition1, $condition2);
        $this->assertEquals($condition1, $condition3);
    }
}
