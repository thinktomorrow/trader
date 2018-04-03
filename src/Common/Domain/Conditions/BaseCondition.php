<?php

namespace Thinktomorrow\Trader\Common\Domain\Conditions;

use Thinktomorrow\Trader\Discounts\Domain\EligibleForDiscount;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\Order;

abstract class BaseCondition implements Condition
{
    protected $parameters = [];

    public function setParameters(array $parameters): Condition
    {
        if (method_exists($this, 'validateParameters')) {
            $this->validateParameters($parameters);
        }

        $this->parameters = $parameters;

        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    protected function forOrderDiscount(EligibleForDiscount $eligibleForDiscount): bool
    {
        return $eligibleForDiscount instanceof Order;
    }

    protected function forItemDiscount(EligibleForDiscount $eligibleForDiscount): bool
    {
        return $eligibleForDiscount instanceof Item;
    }
}
