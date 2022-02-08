<?php

namespace Thinktomorrow\Trader\Tests\Common\Taxes;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Common\Cash\Percentage;
use Thinktomorrow\Trader\Taxes\TaxRate;

class TaxRateTest extends TestCase
{
    /** @test */
    public function it_can_be_set_by_integer()
    {
        $taxRate = TaxRate::fromInteger(21);

        $this->assertEquals(0.21, $taxRate->toPercentage()->toDecimal());
        $this->assertEquals(21, $taxRate->toPercentage()->toInteger());
    }
}
