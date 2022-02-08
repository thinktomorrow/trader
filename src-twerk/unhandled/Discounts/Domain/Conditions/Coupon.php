<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Thinktomorrow\Trader\Discounts\Domain\Condition;
use Thinktomorrow\Trader\Discounts\Domain\Discountable;
use Thinktomorrow\Trader\Order\Domain\Order;

class Coupon implements Condition
{
    private string $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function check(Order $order, Discountable $discountable): bool
    {
        return strtolower($order->getCoupon()) === strtolower($this->code);
    }

//    public function toArray(): array
//    {
//        return [
//            'code' => $this->code,
//        ];
//    }
}
