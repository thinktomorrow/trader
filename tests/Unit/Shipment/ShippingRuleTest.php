<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Shipment\Domain\Conditions\MinimumAmount;
use Thinktomorrow\Trader\Shipment\Domain\ShippingRule;
use Thinktomorrow\Trader\Shipment\Domain\ShippingRuleId;
use Thinktomorrow\Trader\Tests\Unit\Stubs\PurchasableStub;

class ShippingRuleTest extends UnitTestCase
{
    /** @test */
    public function it_has_an_aggregate_id()
    {
        $id = ShippingRuleId::fromInteger(2);

        $this->assertEquals(2, $id->get());
        $this->assertEquals($id, ShippingRuleId::fromInteger(2));
    }

    /** @test */
    public function it_can_create_a_rule()
    {
        $condition = new MinimumAmount();
        $id = ShippingRuleId::fromInteger(1);

        $rule = new ShippingRule($id, [$condition], ['amount' => Money::EUR(120)]);

        $this->assertSame($condition, $rule->conditions()[0]);
        $this->assertSame($id, $rule->id());
    }

    /** @test */
    public function a_rule_without_conditions_is_by_default_applicable_to_an_order()
    {
        $order = $this->makeOrder();

        $rule = new ShippingRule(ShippingRuleId::fromInteger(1), [], ['amount' => Money::EUR(30)]);

        $this->assertTrue($rule->applicable($order));
    }

    /** @test */
    public function a_rule_is_not_applicable_if_conditions_are_not_met()
    {
        $rule = $this->createShippingRule();

        $this->assertFalse($rule->applicable($this->makeOrder()));
    }

    /** @test */
    public function a_rule_can_be_applicable_to_an_order_if_conditions_are_met()
    {
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(1, [], Money::EUR(31))));

        $rule = $this->createShippingRule();

        $this->assertTrue($rule->applicable($order));
    }

    /**
     * @return ShippingRule
     */
    private function createShippingRule(): ShippingRule
    {
        $condition = new MinimumAmount();
        $condition->setParameters(['minimum_amount' => Money::EUR(30)]);

        $rule = new ShippingRule(ShippingRuleId::fromInteger(1), [$condition], ['amount' => Money::EUR(20)]);

        return $rule;
    }
}
