<?php

namespace Thinktomorrow\Trader\Discounts\Application;

use Thinktomorrow\Trader\Discounts\Domain\DiscountCollection;
use Thinktomorrow\Trader\Discounts\Domain\Exceptions\CannotApplyDiscountToOrderException;
use Thinktomorrow\Trader\Orders\Domain\Order;

class ApplyDiscountsToOrder
{
    public function handle(Order $order, DiscountCollection $discounts)
    {
        foreach ($discounts as $discount) {
            try {
                $discount->apply($order);
            } catch (CannotApplyDiscountToOrderException $e) {
                //
            }
        }
    }
}
