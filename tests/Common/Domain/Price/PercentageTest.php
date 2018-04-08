<?php

namespace Thinktomorrow\Trader\Tests\Price;

use Thinktomorrow\Trader\Common\Price\Percentage;
use Thinktomorrow\Trader\Tests\TestCase;

class PercentageTest extends TestCase
{
    /** @test */
    public function it_can_be_set_by_percentage()
    {
        $percentage = Percentage::fromPercent(23);

        $this->assertEquals(0.23, $percentage->asFloat());
        $this->assertEquals(23, $percentage->asPercent());
    }

    /** @test */
    public function it_can_be_set_with_floated_percentage()
    {
        $percentage = Percentage::fromPercent(1.23);

        $this->assertEquals(0.0123, $percentage->asFloat());
        $this->assertEquals(1.23, $percentage->asPercent());
    }

    /** @test */
    public function value_must_be_given()
    {
        $this->expectException(\InvalidArgumentException::class);

        Percentage::fromPercent(null);
    }

    /** @test */
    public function value_can_be_zero()
    {
        $this->assertEquals(0, Percentage::fromPercent(0)->asPercent());
    }

    /** @test */
    public function value_cannot_be_below_zero()
    {
        $this->expectException(\InvalidArgumentException::class);

        Percentage::fromPercent(-2);
    }
}
