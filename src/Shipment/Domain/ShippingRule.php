<?php

namespace Thinktomorrow\Trader\Shipment\Domain;

use Assert\Assertion;
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
    private $impact;

    public function __construct(ShippingRuleId $id, array $conditions, array $impact)
    {
        Assertion::allIsInstanceOf($conditions, Condition::class);

        $this->id = $id;
        $this->conditions = $conditions;
        $this->impact = $impact;
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
}