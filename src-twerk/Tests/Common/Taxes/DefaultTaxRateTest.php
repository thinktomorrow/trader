<?php

namespace Thinktomorrow\Trader\Tests\Common\Taxes;

use Tests\TestCase;
use Thinktomorrow\Trader\Common\Cash\Percentage;
use Thinktomorrow\Trader\Taxes\TaxRate;

class DefaultTaxRateTest extends TestCase
{
    /** @test */
    public function it_can_get_the_default_taxrate_as_set_in_config()
    {
        $taxRate = TaxRate::default();

        $this->assertEquals(Percentage::fromInteger(app()->make('trader_config')->defaultTaxRate()), $taxRate->toPercentage());
    }
}
