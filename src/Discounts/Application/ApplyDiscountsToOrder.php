<?php

namespace Thinktomorrow\Trader\Discounts\Application;

use Thinktomorrow\Trader\Discounts\Domain\CannotApplyDiscountToOrderException;
use Thinktomorrow\Trader\Discounts\Domain\DiscountCollection;
use Thinktomorrow\Trader\Order\Domain\Order;

class ApplyDiscountsToOrder
{
    public function handle(Order $order, DiscountCollection $discounts)
    {
        foreach($discounts as $discount)
        {
            try{
                $appliedDiscount = $discount->apply($order);

                $this->addDiscountToAffectedItems($order, $appliedDiscount);
            }
            catch(CannotApplyDiscountToOrderException $e)
            {
                //
            }
        }
    }

    /**
     * @param Order $order
     * @param $appliedDiscount
     */
    private function addDiscountToAffectedItems(Order $order, $appliedDiscount)
    {
        if ( ! $appliedDiscount->affectsItems()) {
            $order->addDiscount($appliedDiscount);
            return;
        }

        // Add applied discount to each item
        foreach($appliedDiscount->affectedItems() as $item)
        {
            $item->addDiscount($appliedDiscount);
        }
    }
}