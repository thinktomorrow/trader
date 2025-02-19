<?php
declare(strict_types=1);

namespace Tests\Unit\Common\Cash;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Cash\Percentage;

class PercentageTest extends TestCase
{
    public function test_it_can_be_set_by_percentage()
    {
        $percentage = Percentage::fromString('23');

        $this->assertEquals(0.23, $percentage->toDecimal());
        $this->assertEquals(23, $percentage->get());
    }

    public function test_it_can_be_set_with_floated_percentage()
    {
        $percentage = Percentage::fromString('1.23');

        $this->assertEquals(1.23, $percentage->get());
        $this->assertEquals(0.0123, $percentage->toDecimal());
    }

    public function test_it_can_be_set_with_string_percentage()
    {
        $percentage = Percentage::fromString("1.23");

        $this->assertEquals(1.23, $percentage->get());
        $this->assertEquals(0.0123, $percentage->toDecimal());
    }

    public function test_value_can_be_zero()
    {
        $this->assertEquals(0, Percentage::fromString('0')->get());
    }

    public function test_value_cannot_be_below_zero()
    {
        $this->expectException(\InvalidArgumentException::class);

        Percentage::fromString('-2');
    }
}
