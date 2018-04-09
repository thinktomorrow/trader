<?php

namespace Thinktomorrow\Trader\Tests\Discounts\Conditions;

use Thinktomorrow\Trader\Discounts\Domain\Conditions\Period;
use Thinktomorrow\Trader\Tests\TestCase;

class PeriodTest extends TestCase
{
    /** @test */
    public function it_checks_ok_if_no_period_is_enforced()
    {
        $condition = new Period();

        $this->assertTrue($condition->check($order = $this->makeOrder(), $order));
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
        $this->assertTrue($condition->check($order = $this->makeOrder(), $order));

        // Start at tomorrow
        $condition = (new Period())->setParameters(['start_at' => new \DateTimeImmutable('@'.strtotime('+1 day'))]);
        $this->assertFalse($condition->check($order = $this->makeOrder(), $order));

        // End at tomorrow
        $condition = (new Period())->setParameters(['end_at' => new \DateTimeImmutable('@'.strtotime('+1 day'))]);
        $this->assertTrue($condition->check($order = $this->makeOrder(), $order));

        // End at yesterday
        $condition = (new Period())->setParameters(['end_at' => new \DateTimeImmutable('@'.strtotime('-1 day'))]);
        $this->assertFalse($condition->check($order = $this->makeOrder(), $order));

        // Current time falls out of given period
        $condition = (new Period())->setParameters([
            'start_at' => new \DateTimeImmutable('@'.strtotime('+1 day')),
            'end_at'   => new \DateTimeImmutable('@'.strtotime('+2 day')),
        ]);
        $this->assertFalse($condition->check($order = $this->makeOrder(), $order));

        // Current time falls in given period
        $condition = (new Period())->setParameters([
            'start_at' => new \DateTimeImmutable('@'.strtotime('-1 day')),
            'end_at'   => new \DateTimeImmutable('@'.strtotime('+1 day')),
        ]);
        $this->assertTrue($condition->check($order = $this->makeOrder(), $order));
    }

    /** @test */
    public function it_can_be_applied_if_within_given_period()
    {
        $order = $this->makeOrder();

        $discount = $this->makePercentageOffDiscount(15, [
            'start_at' => (new \DateTimeImmutable('@'.strtotime('+3 days'))),
        ]);

        $discount2 = $this->makePercentageOffDiscount(15, [
            'start_at' => (new \DateTimeImmutable('@'.strtotime('-3 days'))),
        ]);

        $this->assertFalse($discount->applicable($order, $order));
        $this->assertTrue($discount2->applicable($order, $order));
    }

    /** @test */
    public function it_can_set_parameters_from_raw_values()
    {
        // Current time falls out of given period
        $condition1 = (new Period())->setParameters([
            'start_at' => new \DateTimeImmutable('@'.strtotime('+1 day')),
            'end_at'   => new \DateTimeImmutable('@'.strtotime('+2 day')),
        ]);

        $condition2 = (new Period())->setRawParameters([
            'start_at' => (new \DateTimeImmutable('@'.strtotime('+1 day')))->format('Y-m-d H:i:s'),
            'end_at'   => (new \DateTimeImmutable('@'.strtotime('+2 day')))->format('Y-m-d H:i:s'),
        ]);

        $this->assertEquals($condition1, $condition2);
    }
}
