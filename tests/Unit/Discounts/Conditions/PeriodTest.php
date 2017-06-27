<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Thinktomorrow\Trader\Discounts\Domain\Conditions\Period;
use Thinktomorrow\Trader\Order\Domain\Order;

class PeriodTest extends UnitTestCase
{
    /** @test */
    function it_checks_ok_if_no_period_is_enforced()
    {
        $condition = new Period;
        $order = $this->makeOrder();

        $this->assertTrue($condition->check([],$order));
    }

    /** @test */
    function passed_date_must_be_a_dateTime()
    {
        $this->setExpectedException(\TypeError::class);

        (new Period())->check([
            'start_at' => 'foobar'
        ],$this->makeOrder());
    }

    /** @test */
    function it_checks_if_given_datetime_is_within_period()
    {
        $this->assertTrue((new Period())->check([
            'start_at' => new \DateTime('@'.strtotime('-1 day'))
        ],$this->makeOrder()));

        $this->assertFalse((new Period())->check([
            'start_at' => new \DateTime('@'.strtotime('+1 day'))
        ],$this->makeOrder()));

        $this->assertTrue((new Period())->check([
            'end_at' => new \DateTime('@'.strtotime('+1 day'))
        ],$this->makeOrder()));

        $this->assertFalse((new Period())->check([
            'end_at' => new \DateTime('@'.strtotime('-1 day'))
        ],$this->makeOrder()));

        $this->assertFalse((new Period())->check([
            'start_at' => new \DateTime('@'.strtotime('+1 day')),
            'end_at' => new \DateTime('@'.strtotime('+2 day'))
        ],$this->makeOrder()));

        $this->assertTrue((new Period())->check([
            'start_at' => new \DateTime('@'.strtotime('-1 day')),
            'end_at' => new \DateTime('@'.strtotime('+1 day'))
        ],$this->makeOrder()));
    }
}