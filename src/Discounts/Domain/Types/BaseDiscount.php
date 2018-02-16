<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

use Assert\Assertion;
use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\ConditionKey;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\EligibleForDiscount;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\Order;

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

    /**
     * @var array
     */
    protected $data;

    public function __construct(DiscountId $id, array $conditions, array $adjusters, array $data = [])
    {
        $this->validateParameters($conditions, $adjusters);

        $this->id = $id;
        $this->conditions = $conditions;
        $this->adjusters = $adjusters;

        // Custom data, e.g. discount text for display on site or shopping cart
        $this->data = $data;
    }

    public function id(): DiscountId
    {
        return $this->id;
    }

    protected function isOrderDiscount(EligibleForDiscount $eligibleForDiscount): bool
    {
        return $eligibleForDiscount instanceof Order;
    }

    protected function isItemDiscount(EligibleForDiscount $eligibleForDiscount): bool
    {
        return $eligibleForDiscount instanceof Item;
    }

    protected function usesCondition(string $condition_key)
    {
        foreach($this->conditions as $condition)
        {
            if($this->getConditionKey($condition_key)->equalsClass($condition))
            {
                return true;
            }
        }

        return false;
    }

    protected function getCondition(string $condition_key)
    {
        if(!isset($this->conditions[$condition_key])) return null;

        return $this->conditions[$condition_key];
    }

    /**
     * Do the conditions apply for the given discountable object.
     *
     * @param Order $order
     *
     * @return bool
     */
    public function applicable(Order $order, EligibleForDiscount $eligibleForDiscount): bool
    {
        if ($this->greaterThanPrice($order, $eligibleForDiscount) || $this->discountAmountBelowZero($order, $eligibleForDiscount)) {
            return false;
        }

        foreach ($this->conditions as $condition) {
            if (false == $condition->check($order, $eligibleForDiscount)) {
                return false;
            }
        }

        return true;
    }

    private function greaterThanPrice(Order $order, EligibleForDiscount $eligibleForDiscount)
    {
        // Protect against negative overflow where order total would dive under zero - DiscountTotal cannot be higher than original price
        $discountTotal = $eligibleForDiscount->discountTotal()->add($this->discountAmount($order, $eligibleForDiscount));

        return $discountTotal->greaterThan($eligibleForDiscount->discountBasePrice());
    }

    private function discountAmountBelowZero(Order $order, EligibleForDiscount $eligibleForDiscount)
    {
        $discountTotal = $eligibleForDiscount->discountTotal()->add($this->discountAmount($order, $eligibleForDiscount));

        return $discountTotal->isNegative();
    }

    /**
     * @param array $conditions
     * @param array $adjusters
     */
    protected function validateParameters(array $conditions, array $adjusters)
    {
        Assertion::allIsInstanceOf($conditions, Condition::class);
    }

    protected function conditionallyAdjustDiscountBasePrice(Money $discountBasePrice, Order $order, string $condition_key)
    {
        if( ! $condition = $this->getCondition($condition_key)) return $discountBasePrice;

        foreach($order->items() as $item)
        {
            if ($condition->check($order, $item)) {
                $discountBasePrice = $discountBasePrice->add($item->total());
            }
        }

        return $discountBasePrice;
    }

    protected function getType(): string
    {
        return TypeKey::fromDiscount($this)->get();
    }

    protected function getConditionKey($string): ConditionKey
    {
        return ConditionKey::fromString($string);
    }
}
