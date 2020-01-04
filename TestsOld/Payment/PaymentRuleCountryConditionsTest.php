<?php

namespace Thinktomorrow\Trader\TestsOld\Payment;

use Money\Money;
use Thinktomorrow\Trader\Payment\Domain\Conditions\Country;
use Thinktomorrow\Trader\Payment\Domain\PaymentRule;
use Thinktomorrow\Trader\Payment\Domain\PaymentRuleId;
use Thinktomorrow\Trader\TestsOld\TestCase;

class PaymentRuleCountryConditionsTest extends TestCase
{
    /** @test */
    public function a_rule_is_not_applicable_if_conditions_are_not_met()
    {
        $order = $this->makeOrder()->setBillingAddress([
            'country_key' => 'BE',
        ]);

        $rule = $this->createPaymentRule();
        $this->assertFalse($rule->applicable($order));
    }

    /** @test */
    public function a_rule_can_be_applicable_to_an_order_if_conditions_are_met()
    {
        $order = $this->makeOrder()->setBillingAddress([
            'country_key' => 'NL',
        ]);

        $rule = $this->createPaymentRule();

        $this->assertTrue($rule->applicable($order));
    }

    /**
     * @return PaymentRule
     */
    private function createPaymentRule(): PaymentRule
    {
        $condition = new Country();
        $condition->setParameters(['country' => 'NL']);

        $rule = new PaymentRule(PaymentRuleId::fromInteger(1), [$condition], ['amount' => Money::EUR(20)]);

        return $rule;
    }
}
