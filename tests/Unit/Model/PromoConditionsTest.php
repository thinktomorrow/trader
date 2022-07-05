<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Promo\ConditionFactory;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumAmount;

class PromoConditionsTest extends TestCase
{
    /** @test */
    public function it_can_create_minimum_amount_model_via_factory()
    {
        $factory = new ConditionFactory([
            MinimumAmount::class,
        ]);

        $condition = $factory->make('minimum_amount', [
            'data' => json_encode(['amount' => '50']),
        ], []);

        $this->assertInstanceOf(MinimumAmount::class, $condition);
    }
}
