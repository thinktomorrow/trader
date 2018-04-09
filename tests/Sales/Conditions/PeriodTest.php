<?php

namespace Thinktomorrow\Trader\Tests\Sales\Conditions;

use Thinktomorrow\Trader\Sales\Domain\Conditions\Period;
use Thinktomorrow\Trader\Tests\TestCase;

class PeriodTest extends TestCase
{
    /** @test */
    public function it_checks_ok_if_no_period_is_enforced()
    {
        $condition = new Period();

        $this->assertTrue($condition->check($this->makeEligibleForSaleStub()));
    }

    /** @test */
    public function passed_date_must_be_a_dateTime()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Period())->setParameters(['start_at' => 'foobar']);
    }

    /** @test */
    public function it_checks_if_given_datetime_is_within_period()
    {
        // Start at yesterday
        $condition = (new Period())->setParameters(['start_at' => new \DateTimeImmutable('@'.strtotime('-1 day'))]);
        $this->assertTrue($condition->check($this->makeEligibleForSaleStub()));

        // Start at tomorrow
        $condition = (new Period())->setParameters(['start_at' => new \DateTimeImmutable('@'.strtotime('+1 day'))]);
        $this->assertFalse($condition->check($this->makeEligibleForSaleStub()));

        // End at tomorrow
        $condition = (new Period())->setParameters(['end_at' => new \DateTimeImmutable('@'.strtotime('+1 day'))]);
        $this->assertTrue($condition->check($this->makeEligibleForSaleStub()));

        // End at yesterday
        $condition = (new Period())->setParameters(['end_at' => new \DateTimeImmutable('@'.strtotime('-1 day'))]);
        $this->assertFalse($condition->check($this->makeEligibleForSaleStub()));

        // Current time falls out of given period
        $condition = (new Period())->setParameters([
            'start_at' => new \DateTimeImmutable('@'.strtotime('+1 day')),
            'end_at'   => new \DateTimeImmutable('@'.strtotime('+2 day')),
        ]);
        $this->assertFalse($condition->check($this->makeEligibleForSaleStub()));

        // Current time falls in given period
        $condition = (new Period())->setParameters([
            'start_at' => new \DateTimeImmutable('@'.strtotime('-1 day')),
            'end_at'   => new \DateTimeImmutable('@'.strtotime('+1 day')),
        ]);
        $this->assertTrue($condition->check($this->makeEligibleForSaleStub()));
    }

    /** @test */
    public function it_can_be_applied_if_within_given_period()
    {
        $stub = $this->makeEligibleForSaleStub();

        $sale = $this->makePercentageOffSale(15, [
            'start_at' => (new \DateTimeImmutable('@'.strtotime('+3 days'))),
        ]);

        $sale2 = $this->makePercentageOffSale(15, [
            'start_at' => (new \DateTimeImmutable('@'.strtotime('-3 days'))),
        ]);

        $this->assertFalse($sale->applicable($stub));
        $this->assertTrue($sale2->applicable($stub));
    }

    /** @test */
    public function it_can_set_parameters_from_raw_values()
    {
        // Current time falls out of given period
        $condition1 = (new Period())->setParameters([
            'start_at' => new \DateTimeImmutable('@'.strtotime('+1 day')),
            'end_at'   => new \DateTimeImmutable('@'.strtotime('+2 day')),
        ]);

        $condition2 = (new Period())->setParameterValues([
            'start_at' => (new \DateTimeImmutable('@'.strtotime('+1 day')))->format('Y-m-d H:i:s'),
            'end_at'   => (new \DateTimeImmutable('@'.strtotime('+2 day')))->format('Y-m-d H:i:s'),
        ]);

        $this->assertEquals($condition1, $condition2);
    }
}
