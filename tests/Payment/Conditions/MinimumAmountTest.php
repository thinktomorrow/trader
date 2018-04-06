<?php

namespace Thinktomorrow\Trader\Tests\Payment\Conditions;

use Money\Money;
use Thinktomorrow\Trader\Payment\Domain\Conditions\MinimumAmount;
use Thinktomorrow\Trader\Tests\TestCase;

class MinimumAmountTest extends TestCase
{
    /** @test */
    public function it_can_set_parameters_from_raw_values()
    {
        // Current time falls out of given period
        $condition1 = (new MinimumAmount())->setParameters([
            'minimum_amount' => Money::EUR(50),
        ]);

        $condition2 = (new MinimumAmount())->setParameterValues([
            'minimum_amount' => 50,
        ]);

        // Parameter as set as single value
        $condition3 = (new MinimumAmount())->setParameterValues(50);

        $this->assertEquals($condition1, $condition2);
        $this->assertEquals($condition1, $condition3);
    }
}
