<?php

namespace Thinktomorrow\Trader\Tests\Unit\Price;

use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Tests\Unit\UnitTestCase;

class PercentageTest extends UnitTestCase
{
    /** @test */
    function it_can_be_set_by_percentage()
    {
        $percentage = Percentage::fromPercent(23);

        $this->assertEquals(0.23,$percentage->asFloat());
        $this->assertEquals(23,$percentage->asPercent());
    }

    /** @test */
    function it_can_be_set_with_floated_percentage()
    {
        $percentage = \Thinktomorrow\Trader\Common\Domain\Price\Percentage::fromPercent(1.23);

        $this->assertEquals(0.0123,$percentage->asFloat());
        $this->assertEquals(1.23,$percentage->asPercent());
    }
}