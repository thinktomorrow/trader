<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Thinktomorrow\Trader\Discounts\Domain\Condition;
use Thinktomorrow\Trader\Discounts\Domain\Discountable;
use Thinktomorrow\Trader\Order\Domain\Order;

class MaximumApplies implements Condition
{
    private int $maximum_applies;
    private int $current_applies;

    public function __construct(int $maximum_applies, int $current_applies)
    {
        $this->maximum_applies = $maximum_applies;
        $this->current_applies = $current_applies;
    }

    public function check(Order $order, Discountable $discountable): bool
    {
        return $this->current_applies < $this->maximum_applies;
    }

//    public function toArray(): array
//    {
//        return [
//            'maximum_applies' => $this->maximum_applies,
//            'current_applies' => $this->current_applies,
//        ];
//    }
}
