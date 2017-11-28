<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Shipment\Domain\Conditions\Country;
use Thinktomorrow\Trader\Shipment\Domain\ShippingRule;
use Thinktomorrow\Trader\Shipment\Domain\ShippingRuleId;

class ShippingRuleCountryConditionsTest extends TestCase
{
    /** @test */
    public function a_rule_is_not_applicable_if_conditions_are_not_met()
    {
        $order = $this->makeOrder()->setShippingAddress([
            'country_key' => 'BE',
        ]);

        $rule = $this->createShippingRule();
        $this->assertFalse($rule->applicable($order));
    }

    /** @test */
    public function a_rule_can_be_applicable_to_an_order_if_conditions_are_met()
    {
        $order = $this->makeOrder()->setShippingAddress([
            'country_key' => 'NL',
        ]);

        $rule = $this->createShippingRule();

        $this->assertTrue($rule->applicable($order));
    }

    /**
     * @return ShippingRule
     */
    private function createShippingRule(): ShippingRule
    {
        $condition = new Country();
        $condition->setParameters(['country' => 'NL']);

        $rule = new ShippingRule(ShippingRuleId::fromInteger(1), [$condition], ['amount' => Money::EUR(20)]);

        return $rule;
    }
}
