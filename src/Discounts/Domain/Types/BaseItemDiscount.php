<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

use Assert\Assertion;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\ItemId;
use Thinktomorrow\Trader\Orders\Domain\Order;

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
     * Do the conditions apply for the given item.
     *
     * @param Order  $order
     * @param ItemId $itemId
     *
     * @return bool
     */
    public function applicable(Order $order, ItemId $itemId): bool
    {
        if (!$item = $order->items()->find($itemId)) {
            return false;
        }

        foreach ($this->conditions as $condition) {
            if (false == $condition->check($order, $item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * On how many items does the discount apply?
     * By default an item discount applies to the total quantity of each item.
     *
     * @param Item $item
     *
     * @return int
     */
    public function getAffectedItemQuantity(Item $item)
    {
        $maximum = $item->quantity();

        if (isset($this->adjusters['maximum_affected_quantity'])) {
            $forced_maximum = (int) $this->adjusters['maximum_affected_quantity'];

            if ($forced_maximum < $maximum) {
                $maximum = $forced_maximum;
            }
        }

        return $maximum;
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
