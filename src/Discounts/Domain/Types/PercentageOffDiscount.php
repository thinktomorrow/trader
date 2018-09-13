<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

use Assert\Assertion;
use Money\Money;
use Thinktomorrow\Trader\Common\Adjusters\Adjuster;
use Thinktomorrow\Trader\Common\Adjusters\Percentage;
use Thinktomorrow\Trader\Common\Price\Cash;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\Discount;
use Thinktomorrow\Trader\Discounts\Domain\EligibleForDiscount;
use Thinktomorrow\Trader\Discounts\Domain\Exceptions\CannotApplyDiscount;
use Thinktomorrow\Trader\Orders\Domain\Order;

class PercentageOffDiscount extends BaseDiscount implements Discount
{
    public function apply(Order $order, EligibleForDiscount $eligibleForDiscount)
    {
        if (!$this->applicable($order, $eligibleForDiscount)) {
            throw new CannotApplyDiscount('Discount cannot be applied. One or more conditions have failed.');
        }

        $discountBasePrice = $eligibleForDiscount->discountBasePrice();
        $discountAmount = $this->discountAmount($order, $eligibleForDiscount);

        $eligibleForDiscount->addToDiscountTotal($discountAmount);
        $eligibleForDiscount->addDiscount(new AppliedDiscount(
            $this->id,
            $this->getType(),
            $discountAmount,
            $discountBasePrice,
            Cash::from($discountAmount)->asPercentage($discountBasePrice, 0),
            $this->mergeRawConditions($this->data)
        ));
    }

    public function discountAmount(Order $order, EligibleForDiscount $eligibleForDiscount): Money
    {
        $discountBasePrice = $this->discountBasePrice($order, $eligibleForDiscount);
        $discountBasePriceMinusDiscounts = $discountBasePrice->subtract($eligibleForDiscount->discountTotal());

        $discountAmount = $discountBasePrice->multiply($this->adjuster->getParameter('percentage')->asFloat());

        return $discountBasePriceMinusDiscounts->lessThanOrEqual($discountAmount)
            ? $discountBasePriceMinusDiscounts
            : $discountAmount;
    }

    public function discountBasePrice(Order $order, EligibleForDiscount $eligibleForDiscount): Money
    {
        // IF ORDERDISCOUNT USE GLOBAL DISCOUNT BUT CHECK IF WE HAVE A ITEM_WHITELIST OR ITEM_BLACKLIST TO CALCULATE THE DISCOUNT AMOUNT UPON
        if ($this->isItemDiscount($eligibleForDiscount) || (!$this->usesCondition('item_whitelist') && !$this->usesCondition('item_blacklist'))) {
            return $eligibleForDiscount->discountBasePrice();
        }

        return $this->adjustDiscountBasePriceByConditions(Cash::make(0), $order, $this->conditions);
    }

    protected function validateParameters(array $conditions, Adjuster $adjuster)
    {
        parent::validateParameters($conditions, $adjuster);

        Assertion::isInstanceOf($adjuster, Percentage::class);
    }
}
