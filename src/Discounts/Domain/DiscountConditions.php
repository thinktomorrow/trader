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

    public function applicableToOrder(Order $order): bool
    {
        if( ! $this->appliesToEntireOrder() ) return false;

        foreach($this->available_conditions as $condition)
        {
            if(false == (new $condition)->check($this->conditions, $order)) return false;
        }

        return true;
    }

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

    public function getAffectedItemQuantity(): int
    {
        // TODO: Should default be only one item to be discounted?
        if( ! isset($this->conditions['maximum_affected_item_quantity'])) return 1;

        return (int) $this->conditions['maximum_affected_item_quantity'];
    }

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

    private function appliesToEntireOrder()
    {
        return !(isset($this->conditions['applies_to']) && $this->conditions['applies_to'] != 'order');
    }

    private function appliesToItem($itemId)
    {
        if( ! isset($this->conditions['purchasable_ids'])) return true;

        if(isset($this->conditions['purchasable_ids']) && in_array($itemId, (array)$this->conditions['purchasable_ids']))
        {
            return true;
        }

        return false;
    }
}