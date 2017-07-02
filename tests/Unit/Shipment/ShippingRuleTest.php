<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Shipment\Domain\Conditions\MinimumAmount;
use Thinktomorrow\Trader\Shipment\Domain\ShippingRule;
use Thinktomorrow\Trader\Shipment\Domain\ShippingRuleFactory;
use Thinktomorrow\Trader\Shipment\Domain\ShippingRuleId;
use Thinktomorrow\Trader\Tests\DummyContainer;
use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePurchasable;

class ShippingRuleTest extends UnitTestCase
{
    /** @test */
    function it_has_an_aggregate_id()
    {
        $id = ShippingRuleId::fromInteger(2);

        $this->assertEquals(2,$id->get());
        $this->assertEquals($id, ShippingRuleId::fromInteger(2));
    }

    /** @test */
    function it_can_create_rule_from_flat_settings()
    {
        $rule = (new ShippingRuleFactory(new DummyContainer))->create(1, [
            'minimum_amount' => 20,
        ],[
            'cost' => 10
        ]);

        $this->assertInstanceOf(ShippingRule::class, $rule);
        $this->assertInstanceOf(MinimumAmount::class, $rule->conditions()[0]);
    }

    /** @test */
    function it_can_create_a_rule_with_conditions()
    {
        $condition = new MinimumAmount();
        $id = ShippingRuleId::fromInteger(1);

        $rule = new ShippingRule($id,[
            $condition
        ],['minimum_amount' => 20],['base_cost' => Money::EUR(120)]);

        $this->assertSame($condition, $rule->conditions()[0]);
        $this->assertSame($id, $rule->id());
    }

    /** @test */
    function a_rule_without_conditions_is_by_default_applicable_to_an_order()
    {
        $order = $this->makeOrder();

        $rule = new ShippingRule(ShippingRuleId::fromInteger(1),[],[],[]);

        $this->assertTrue($rule->applicable($order));
    }

    /** @test */
    function a_rule_is_not_applicable_if_conditions_are_not_met()
    {
        $condition = new MinimumAmount();
        $condition->setParameters(['minimum_amount' => Money::EUR(30)]);

        $rule = new ShippingRule(ShippingRuleId::fromInteger(1),[$condition],[]);

        $this->assertFalse($rule->applicable($this->makeOrder()));
    }

    /** @test */
    function a_rule_can_be_applicable_to_an_order_if_conditions_are_met()
    {
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(1,[],Money::EUR(31))));

        $condition = new MinimumAmount();
        $condition->setParameters(['minimum_amount' => Money::EUR(30)]);

        $rule = new ShippingRule(ShippingRuleId::fromInteger(1),[$condition],[]);

        $this->assertTrue($rule->applicable($order));
    }

}