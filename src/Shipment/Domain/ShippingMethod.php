<?php

namespace Thinktomorrow\Trader\Shipment\Domain;

use Assert\Assertion;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Shipment\Domain\Exceptions\CannotApplyShippingRuleException;

class ShippingMethod
{
    /**
     * @var ShippingMethodId
     */
    private $id;

    /**
     * @var ShippingRule[]
     */
    private $rules;

    /**
     * Request cache of matching rule (avoid redundant matching logic).
     *
     * @var ShippingRule
     */
    private $applicableRule;

    /**
     * Unique string identifier.
     * Can be used by your application to identify a method
     * @var string
     */
    private $code;

    public function __construct(ShippingMethodId $id, string $code, array $rules = [])
    {
        Assertion::allIsInstanceOf($rules, ShippingRule::class);

        $this->id = $id;
        $this->code = $code;
        $this->rules = $rules;
    }

    public function id(): ShippingMethodId
    {
        return $this->id;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function apply(Order $order)
    {
        if (!$this->applicable($order)) {
            throw new CannotApplyShippingRuleException('Shipping method ['.$this->id().'] not applicable to order ['.$order->id().']. None of the rules match.');
        }

        $shippingRule = $this->getApplicableRule($order);

        $order->setShipping($this->id(), $shippingRule->id());

        // TODO: Adjuster should be altering the order, not straight in the shippingMethod. This way the adjustment logic
        // Is more flexible and also usable for discounts, sales,...
        $order->setShippingTotal($shippingRule->total());
    }

    public function applicable(Order $order): bool
    {
        return (bool) $this->getApplicableRule($order);
    }

    private function getApplicableRule(Order $order)
    {
        // Return early if rule has already been determined
        if ($this->applicableRule) {
            return $this->applicableRule;
        }

        foreach ($this->rules as $rule) {
            if ($rule->applicable($order)) {
                return $this->applicableRule = $rule;
            }
        }
    }
}
