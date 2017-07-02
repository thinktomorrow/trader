<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

use Assert\Assertion;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Domain\ItemId;
use Thinktomorrow\Trader\Order\Domain\Order;

abstract class BaseItemDiscount
{
    protected static $isItemDiscount = true;

    /**
     * @var DiscountId
     */
    protected $id;

    /**
     * @var Condition[]
     */
    protected $conditions;

    public function id(): DiscountId
    {
        return $this->id;
    }

    /**
     * Do the conditions apply for the given order
     *
     * @param Order $order
     * @param ItemId $itemId
     * @return bool
     */
    public function applicable(Order $order, ItemId $itemId): bool
    {
        if( ! $item = $order->items()->find($itemId) ) return false;

        foreach($this->conditions as $condition)
        {
            if(false == $condition->check($order, $item)) return false;
        }

        return true;
    }

    /**
     * On how many items does the discount apply?
     * By default an item discount applies to the total quantity of each item
     *
     * @param Item $item
     * @return int
     */
    public function getAffectedItemQuantity(Item $item)
    {
        if(isset($this->conditions['maximum_affected_item_quantity']))
        {
            return (int) $this->conditions['maximum_affected_item_quantity'];
        }

        return $item->quantity();
    }

    /**
     * @param array $conditions
     * @param array $adjusters
     */
    protected function validateParameters(array $conditions, array $adjusters)
    {
        Assertion::allIsInstanceOf($conditions, Condition::class);
    }
}