<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Thinktomorrow\Trader\Discounts\Domain\Conditions\Period;

class PeriodTest extends UnitTestCase
{
    /** @test */
    function it_checks_ok_if_no_period_is_enforced()
    {
        $condition = new Period;

        $this->assertTrue($condition->check($this->makeOrder()));
    }

    /** @test */
    function passed_date_must_be_a_dateTime()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Period())->setParameters(['start_at' => 'foobar']);
    }

    /** @test */
    function it_checks_if_given_datetime_is_within_period()
    {
        // Start at yesterday
        $condition = (new Period())->setParameters(['start_at' => new \DateTime('@'.strtotime('-1 day'))]);
        $this->assertTrue($condition->check($this->makeOrder()));

        // Start at tomorrow
        $condition = (new Period())->setParameters(['start_at' => new \DateTime('@'.strtotime('+1 day'))]);
        $this->assertFalse($condition->check($this->makeOrder()));

        // End at tomorrow
        $condition = (new Period())->setParameters(['end_at' => new \DateTime('@'.strtotime('+1 day'))]);
        $this->assertTrue($condition->check($this->makeOrder()));

        // End at yesterday
        $condition = (new Period())->setParameters(['end_at' => new \DateTime('@'.strtotime('-1 day'))]);
        $this->assertFalse($condition->check($this->makeOrder()));

        // Current time falls out of given period
        $condition = (new Period())->setParameters([
            'start_at' => new \DateTime('@'.strtotime('+1 day')),
            'end_at' => new \DateTime('@'.strtotime('+2 day'))
        ]);
        $this->assertFalse($condition->check($this->makeOrder()));

        // Current time falls in given period
        $condition = (new Period())->setParameters([
            'start_at' => new \DateTime('@'.strtotime('-1 day')),
            'end_at' => new \DateTime('@'.strtotime('+1 day'))
        ]);
        $this->assertTrue($condition->check($this->makeOrder()));
    }
}