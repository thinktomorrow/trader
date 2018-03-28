<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Thinktomorrow\Trader\Common\Domain\Conditions\BaseCondition;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Common\Domain\Conditions\ItemCondition;
use Thinktomorrow\Trader\Discounts\Domain\EligibleForDiscount;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\Order;

class ItemBlacklist extends BaseCondition implements Condition
{
    public function check(Order $order, EligibleForDiscount $eligibleForDiscount): bool
    {
        /**
         * If condition runs for an orderdiscount, we ignore the blacklist as
         * condition because it is used to calculate the discount baseprice
         */
        if (!isset($this->parameters['item_blacklist']) || $this->forOrderDiscount($eligibleForDiscount)) {
            return true;
        }

        return $this->checkItem($order, $eligibleForDiscount);
    }

    public function getParameterValues(): array
    {
        return [
            'item_blacklist' => $this->parameters['item_blacklist']
        ];
    }

    private function checkItem(Order $order, Item $item): bool
    {
        return !in_array($item->purchasableId()->get(), $this->parameters['item_blacklist']);
    }

    protected function validateParameters(array $parameters)
    {
        if (isset($parameters['item_blacklist']) && !is_array($parameters['item_blacklist'])) {
            throw new \InvalidArgumentException('Condition value for item_blacklist must be an array of ids. '.gettype($parameters['item_blacklist']).' given.');
        }
    }
}
