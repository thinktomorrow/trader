<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Thinktomorrow\Trader\Discounts\Domain\EligibleForDiscount;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\Order;

class ItemWhitelist extends BaseCondition implements DiscountCondition
{
    public function check(Order $order, EligibleForDiscount $eligibleForDiscount): bool
    {
        /**
         * If condition runs for an orderdiscount, we ignore the whitelist as
         * condition because it is used to calculate the discount baseprice
         */
        if (!isset($this->parameters['item_whitelist']) || $this->forOrderDiscount($eligibleForDiscount)) {
            return true;
        }

        return $this->checkItem($order, $eligibleForDiscount);
    }

    private function checkItem(Order $order, Item $item): bool
    {
        return in_array($item->purchasableId()->get(), $this->parameters['item_whitelist']);
    }

    protected function validateParameters(array $parameters)
    {
        if ( ! isset($parameters['item_whitelist'])) {
            throw new \InvalidArgumentException('Condition parameter item_whitelist is missing.');
        }

        if (!is_array($parameters['item_whitelist'])) {
            throw new \InvalidArgumentException('Condition value for item_whitelist must be an array of ids. '.gettype($parameters['item_whitelist']).' given.');
        }
    }
}
