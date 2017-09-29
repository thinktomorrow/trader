<?php

namespace Thinktomorrow\Trader\Tests\Payment;

use Money\Money;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Payment\Domain\Conditions\MinimumAmount;
use Thinktomorrow\Trader\Payment\Domain\PaymentRule;
use Thinktomorrow\Trader\Payment\Domain\PaymentRuleId;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;
use Thinktomorrow\Trader\Tests\UnitTestCase;

class PaymentRuleTest extends UnitTestCase
{
    /** @test */
    public function it_has_an_aggregate_id()
    {
        $id = PaymentRuleId::fromInteger(2);

        $this->assertEquals(2, $id->get());
        $this->assertEquals($id, PaymentRuleId::fromInteger(2));
    }

    /** @test */
    public function it_can_create_a_rule()
    {
        $condition = new MinimumAmount();
        $id = PaymentRuleId::fromInteger(1);

        $rule = new PaymentRule($id, [$condition], ['amount' => Money::EUR(120)]);

        $this->assertSame($condition, $rule->conditions()[0]);
        $this->assertSame($id, $rule->id());
    }

    /** @test */
    public function a_rule_without_conditions_is_by_default_applicable_to_an_order()
    {
        $order = $this->makeOrder();

        $rule = new PaymentRule(PaymentRuleId::fromInteger(1), [], ['amount' => Money::EUR(30)]);

        $this->assertTrue($rule->applicable($order));
    }

    /** @test */
    public function a_rule_is_not_applicable_if_conditions_are_not_met()
    {
        $rule = $this->createPaymentRule();

        $this->assertFalse($rule->applicable($this->makeOrder()));
    }

    /** @test */
    public function a_rule_can_be_applicable_to_an_order_if_conditions_are_met()
    {
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(1, [], Money::EUR(31))));

        $rule = $this->createPaymentRule();

        $this->assertTrue($rule->applicable($order));
    }

    /**
     * @return PaymentRule
     */
    private function createPaymentRule(): PaymentRule
    {
        $condition = new MinimumAmount();
        $condition->setParameters(['minimum_amount' => Money::EUR(30)]);

        $rule = new PaymentRule(PaymentRuleId::fromInteger(1), [$condition], ['amount' => Money::EUR(20)]);

        return $rule;
    }
}
