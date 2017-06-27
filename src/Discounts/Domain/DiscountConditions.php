<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\MaximumAffectedItemQuantity;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\MinimumAmount;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\MinimumItemAmount;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\Period;
use Thinktomorrow\Trader\Order\Domain\Order;

class DiscountConditions
{
    /**
     * @var array
     */
    private $conditions;

    private $available_conditions = [
        Period::class,
        MinimumAmount::class,
    ];

    private $available_item_conditions = [
        Period::class,
        MinimumItemAmount::class,
    ];

    public function __construct(array $conditions)
    {
        $this->validateConditions($conditions);

        $this->conditions = $conditions;
    }

    public function add($key, $value)
    {
        return new self(array_merge($this->conditions,[$key => $value]));
    }

    /**
     * Do the conditions apply for the given order
     *
     * @param Order $order
     * @return bool
     */
    public function applicableToOrder(Order $order): bool
    {
        if( ! $this->appliesToEntireOrder() ) return false;

        foreach($this->available_conditions as $condition)
        {
            if(false == (new $condition)->check($this->conditions, $order)) return false;
        }

        return true;
    }

    /**
     * Do the conditions apply for the ordered item
     *
     * @param Order $order
     * @param $itemId
     * @return bool
     */
    public function applicableToItem(Order $order, $itemId): bool
    {
        if( $this->appliesToEntireOrder() ) return false;
        if( ! $item = $order->items()->find($itemId) ) return false;
        if( ! $this->appliesToItem($item->id())) return false;

        foreach($this->available_item_conditions as $condition)
        {
            if(false == (new $condition)->check($this->conditions, $order, $item)) return false;
        }

        return true;
    }

    /**
     * On how many items does the discount apply?
     *
     * @return int
     */
    public function getAffectedItemQuantity(): int
    {
        if( ! isset($this->conditions['maximum_affected_item_quantity'])) return 1;

        return (int) $this->conditions['maximum_affected_item_quantity'];
    }

    /**
     * @return bool
     */
    private function appliesToEntireOrder()
    {
        return !(isset($this->conditions['applies_to']) && $this->conditions['applies_to'] != 'order');
    }

    /**
     * @param $itemId
     * @return bool
     */
    private function appliesToItem($itemId)
    {
        if( ! isset($this->conditions['purchasable_ids'])) return true;

        if(isset($this->conditions['purchasable_ids']) && in_array($itemId, (array)$this->conditions['purchasable_ids']))
        {
            return true;
        }

        return false;
    }

    /**
     * Validate the integrity of the given conditions
     *
     * @param array $conditions
     */
    private function validateConditions(array $conditions)
    {
        if(isset($conditions['minimum_amount']) && ! $conditions['minimum_amount'] instanceof Money)
        {
            throw new \InvalidArgumentException('DiscountCondition value for minimum amount must be instance of Money.');
        }

        if(isset($conditions['start_at']) && ! $conditions['start_at'] instanceof \DateTime)
        {
            throw new \InvalidArgumentException('DiscountCondition value for start_at must be instance of DateTime.');
        }

        if(isset($conditions['end_at']) && ! $conditions['end_at'] instanceof \DateTime)
        {
            throw new \InvalidArgumentException('DiscountCondition value for end_at must be instance of DateTime.');
        }
    }
}