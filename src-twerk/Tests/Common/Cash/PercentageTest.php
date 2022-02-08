<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Common\Cash;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Common\Cash\Percentage;

class PercentageTest extends TestCase
{
    /** @test */
    public function it_can_be_set_by_percentage()
    {
        $percentage = Percentage::fromInteger(23);

        $this->assertEquals(0.23, $percentage->toDecimal());
        $this->assertEquals(23, $percentage->toInteger());
    }

    /** @test */
    public function it_can_be_set_with_floated_percentage()
    {
        $percentage = Percentage::fromFloat(1.23);

        $this->assertEquals(1.23, $percentage->get());
        $this->assertEquals(0.0123, $percentage->toDecimal());
        $this->assertEquals(1, $percentage->toInteger());
    }

    /** @test */
    public function it_can_be_set_with_string_percentage()
    {
        $percentage = Percentage::fromString("1.23");

        $this->assertEquals(1.23, $percentage->get());
        $this->assertEquals(0.0123, $percentage->toDecimal());
        $this->assertEquals(1, $percentage->toInteger());
    }

    /** @test */
    public function value_can_be_zero()
    {
        $this->assertEquals(0, Percentage::fromInteger(0)->toInteger());
    }

    /** @test */
    public function value_cannot_be_below_zero()
    {
        $this->expectException(\InvalidArgumentException::class);

        Percentage::fromInteger(-2);
    }
}
