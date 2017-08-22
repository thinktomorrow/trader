<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Thinktomorrow\Trader\Common\Domain\Conditions\BaseCondition;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Common\Domain\Conditions\ItemCondition;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\Order;

class ItemWhitelist extends BaseCondition implements Condition, ItemCondition
{
    public function check(Order $order, Item $item): bool
    {
        if (!isset($this->parameters['purchasable_ids'])) {
            return true;
        }

        return in_array($item->id()->get(), $this->parameters['purchasable_ids']);
    }

    protected function validateParameters(array $parameters)
    {
        if (isset($parameters['purchasable_ids']) && !is_array($parameters['purchasable_ids'])) {
            throw new \InvalidArgumentException('Condition value for purchasable_ids must be an array of ids. '.gettype($parameters['purchasable_ids']).' given.');
        }
    }
}
