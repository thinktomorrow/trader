<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Thinktomorrow\Trader\Common\Conditions\Condition;
use Thinktomorrow\Trader\Common\Helpers\HandlesParameters;
use Thinktomorrow\Trader\Common\Helpers\HandlesType;
use Thinktomorrow\Trader\Discounts\Domain\EligibleForDiscount;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\Order;

abstract class BaseCondition implements Condition
{
    use HandlesParameters, HandlesType;

    protected $parameters = [];

    public function __construct(string $type = null)
    {
        $this->type = $type ?? $this->guessType();
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
