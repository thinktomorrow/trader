<?php

namespace Thinktomorrow\Trader\Shipment\Domain;

use Assert\Assertion;
use Thinktomorrow\Trader\Order\Domain\Order;
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
     * Request cache of matching rule (avoid redundant matching logic)
     *
     * @var ShippingRule
     */
    private $applicableRule;

    public function __construct(ShippingMethodId $id, array $rules = [])
    {
        Assertion::allIsInstanceOf($rules, ShippingRule::class);

        $this->id = $id;
        $this->rules = $rules;
    }

    public function id(): ShippingMethodId
    {
        return $this->id;
    }

    public function apply(Order $order): ShippingRule
    {
        if( ! $this->applicable($order))
        {
            throw new CannotApplyShippingRuleException('Shipping method ['.$this->id().'] not applicable to order ['.$order->id().']. None of the rules match.');
        }

        $shipmentRule = $this->getApplicableRule($order);

        $order->setShipment($this->id(),$shipmentRule->id());
        $order->setShipmentTotal($shipmentRule->total());
    }

    public function applicable(Order $order): bool
    {
        return !! $this->getApplicableRule($order);
    }

    private function getApplicableRule(Order $order)
    {
        // Return early if rule has already been determined
        if($this->applicableRule) return $this->applicableRule;

        foreach($this->rules as $rule)
        {
            if($rule->applicable($order)) return $this->applicableRule = $rule;
        }

        return null;
    }
}