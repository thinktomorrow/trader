<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Thinktomorrow\Trader\Discounts\Domain\Condition;
use Thinktomorrow\Trader\Discounts\Domain\Discountable;
use Thinktomorrow\Trader\Order\Domain\Order;

class MaximumCustomerApplies implements Condition
{
    private int $maximum_customer_applies;
    private int $current_customer_applies;

    public function __construct(int $maximum_customer_applies, int $current_customer_applies)
    {
        $this->maximum_customer_applies = $maximum_customer_applies;
        $this->current_customer_applies = $current_customer_applies;
    }

    public function check(Order $order, Discountable $discountable): bool
    {
        return $this->current_customer_applies < $this->maximum_customer_applies;
    }

//    public function toArray(): array
//    {
//        return [
//            'maximum_customer_applies' => $this->maximum_customer_applies,
//            'current_customer_applies' => $this->current_customer_applies,
//        ];
//    }
}
