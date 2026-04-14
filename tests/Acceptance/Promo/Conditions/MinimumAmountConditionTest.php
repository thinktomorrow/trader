<?php

declare(strict_types=1);

namespace Tests\Acceptance\Promo\Conditions;

use Tests\Acceptance\TestCase;
use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\Conditions\MinimumAmountOrderCondition;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderConditionFactory;

class MinimumAmountConditionTest extends TestCase
{
    use TestHelpers;

    private OrderConditionFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new OrderConditionFactory([
            MinimumAmountOrderCondition::class,
        ]);
    }

    public function test_it_can_create_condition_via_factory()
    {
        $condition = $this->factory->make('minimum_amount', [
            'data' => json_encode(['amount' => '50']),
        ], []);

        $this->assertInstanceOf(MinimumAmountOrderCondition::class, $condition);
    }

    public function test_it_can_check_condition()
    {
        $order = $this->orderContext->createDefaultOrder();

        $condition = $this->factory->make('minimum_amount', [
            'data' => json_encode(['amount' => '100']),
        ], []);

        $this->assertTrue($condition->check($order, $order));

        $condition = $this->factory->make('minimum_amount', [
            'data' => json_encode(['amount' => '1000']),
        ], []);

        $this->assertFalse($condition->check($order, $order));
    }
}
