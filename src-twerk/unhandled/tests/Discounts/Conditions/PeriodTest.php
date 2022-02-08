<?php

namespace Thinktomorrow\Trader\Tests\Discounts\Conditions;

use Carbon\Carbon;
use Tests\TestCase;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\Period;

class PeriodTest extends TestCase
{
    /** @test */
    public function discount_passes_when_within_period()
    {
        $condition = new Period(
            \DateTimeImmutable::createFromMutable(Carbon::now()->subDay()),
            \DateTimeImmutable::createFromMutable(Carbon::now()->addDay())
        );

        $order = $this->emptyOrder('xxx');

        $this->assertTrue($condition->check($order, $order));
    }

    /** @test */
    public function discount_does_not_pass_when_outside_the_period()
    {
        $condition = new Period(
            \DateTimeImmutable::createFromMutable(Carbon::now()->addDay()),
            \DateTimeImmutable::createFromMutable(Carbon::now()->addDays(2))
        );

        $order = $this->emptyOrder('xxx');

        $this->assertFalse($condition->check($order, $order));
    }
}
