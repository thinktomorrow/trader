<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

use Assert\Assertion;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Common\Domain\Conditions\ItemCondition;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Order\Domain\Order;

abstract class BaseDiscount
{
    /**
     * @var DiscountId
     */
    protected $id;

    /**
     * @var Condition[]
     */
    protected $conditions;

    /**
     * @var array
     */
    protected $adjusters;

    public function __construct(DiscountId $id, array $conditions, array $adjusters)
    {
        $this->validateParameters($conditions, $adjusters);

        $this->id = $id;
        $this->conditions = $conditions;
        $this->adjusters = $adjusters;
    }

    public function id(): DiscountId
    {
        return $this->id;
    }

    /**
     * Do the conditions apply for the given order.
     *
     * @param Order $order
     *
     * @return bool
     */
    public function applicable(Order $order): bool
    {
        foreach ($this->conditions as $condition) {
            if (false == $condition->check($order)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $conditions
     * @param array $adjusters
     */
    protected function validateParameters(array $conditions, array $adjusters)
    {
        Assertion::allIsInstanceOf($conditions, Condition::class);
        Assertion::allNotIsInstanceOf($conditions, ItemCondition::class);
    }
}
