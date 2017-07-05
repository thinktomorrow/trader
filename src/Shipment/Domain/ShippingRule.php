<?php

namespace Thinktomorrow\Trader\Shipment\Domain;

use Assert\Assertion;
use Money\Money;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;

class ShippingRule
{
    /**
     * @var ShippingRuleId
     */
    private $id;

    /**
     * @var array
     */
    private $conditions;

    /**
     * @var array
     */
    private $adjusters;

    /**
     * @var Money
     */
    private $amount;

    public function __construct(ShippingRuleId $id, array $conditions, array $adjusters)
    {
        $this->validateParameters($conditions, $adjusters);

        $this->id = $id;
        $this->conditions = $conditions;
        $this->amount = $adjusters['amount'];
        $this->adjusters = $adjusters;
    }

    public function applicable(Order $order): bool
    {
        foreach($this->conditions as $condition)
        {
            if( false == $condition->check($order)) return false;
        }

        return true;
    }

    public function id(): ShippingRuleId
    {
        return $this->id;
    }

    public function conditions(): array
    {
        return $this->conditions;
    }

    public function total(): Money
    {
        return $this->amount;
    }

    /**
     * @param array $conditions
     * @param array $adjusters
     */
    private function validateParameters(array $conditions, array $adjusters)
    {
        Assertion::allIsInstanceOf($conditions, Condition::class);

        if (!isset($adjusters['amount']) || !$adjusters['amount'] instanceof Money) {
            throw new \InvalidArgumentException('Invalid or missing amount. ShippingRule requires at least an amount adjuster of type Money.');
        }
    }
}