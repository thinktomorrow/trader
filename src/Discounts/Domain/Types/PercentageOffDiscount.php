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
            array_merge(
                $this->mergeRawConditions($this->data),
                ['basetype' => $this->getBaseType()]
            )
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
        if($this->isOrderDiscount($eligibleForDiscount) &&  ! empty($this->conditions)) {
            return $this->adjustDiscountBasePriceByConditions(
                Cash::make(0), $eligibleForDiscount, $this->conditions
            );
        }

        return $eligibleForDiscount->discountBasePrice();
    }

    protected function validateParameters(array $conditions, Adjuster $adjuster)
    {
        parent::validateParameters($conditions, $adjuster);

        Assertion::isInstanceOf($adjuster, Percentage::class);
    }
}
