<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

use Assert\Assertion;
use Money\Money;
use Thinktomorrow\Trader\Common\Adjusters\Adjuster;
use Thinktomorrow\Trader\Common\Conditions\Condition;
use Thinktomorrow\Trader\Discounts\Domain\AdjustDiscountBasePrice;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\ConditionKey;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\DiscountCondition;
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
     * @var Adjuster
     */
    protected $adjuster;

    /**
     * @var array
     */
    protected $data;

    public function __construct(DiscountId $id, array $conditions, Adjuster $adjuster, array $data = [])
    {
        $this->validateParameters($conditions, $adjuster);

        $this->id = $id;
        $this->conditions = $conditions;
        $this->adjuster = $adjuster;

        // Custom data, e.g. discount text for display on site or shopping cart
        $this->data = $data;
    }

    protected function validateParameters(array $conditions, Adjuster $adjuster)
    {
        Assertion::allIsInstanceOf($conditions, DiscountCondition::class);
    }

    public function id(): DiscountId
    {
        return $this->id;
    }

    public function getType(): string
    {
        return TypeKey::fromDiscount($this)->get();
    }

    public function discountBasePrice(Order $order, EligibleForDiscount $eligibleForDiscount): Money
    {
        return $eligibleForDiscount->discountBasePrice();
    }

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

    public function usesCondition(string $condition_key): bool
    {
        foreach ($this->conditions as $condition) {
            if ($this->getConditionKey($condition_key)->equalsClass($condition)) {
                return true;
            }
        }

        return false;
    }

    protected function isOrderDiscount(EligibleForDiscount $eligibleForDiscount): bool
    {
        return $eligibleForDiscount instanceof Order;
    }

    protected function isItemDiscount(EligibleForDiscount $eligibleForDiscount): bool
    {
        return $eligibleForDiscount instanceof Item;
    }

    protected function adjustDiscountBasePriceByConditions(Money $discountBasePrice, Order $order, array $conditions = [])
    {
        return (new AdjustDiscountBasePrice())->setDiscountBasePrice($discountBasePrice)
            ->setOrder($order)
            ->addConditions($conditions)
            ->discountBasePrice();
    }

    protected function mergeRawConditions(array $array): array
    {
        $conditions = [];

        foreach($this->conditions as $condition)
        {
            $conditions = array_merge($conditions, $condition->getRawParameters());
        }

        if(!empty($conditions)){
            $array = array_merge(['conditions' => $conditions], $array);
        }

        return $array;
    }

    protected function getCondition(string $condition_key)
    {
        if (!isset($this->conditions[$condition_key])) {
            return;
        }

        return $this->conditions[$condition_key];
    }

    protected function getConditionKey($string): ConditionKey
    {
        return ConditionKey::fromString($string);
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
}
