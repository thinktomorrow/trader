<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

use Assert\Assertion;
use Money\Money;
use Thinktomorrow\Trader\Common\Price\Cash;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\Discount;
use Thinktomorrow\Trader\Discounts\Domain\EligibleForDiscount;
use Thinktomorrow\Trader\Discounts\Domain\Exceptions\CannotApplyDiscount;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\Order;

final class FreeItemDiscount extends BaseDiscount implements Discount
{
    /**
     * Adds a free product to the cart based on given conditions.
     *
     * @param Order $order
     *
     * @throws CannotApplyDiscount
     */
    public function apply(Order $order, EligibleForDiscount $eligibleForDiscount)
    {
        // Check conditions first
        if (!$this->applicable($order, $eligibleForDiscount)) {
            throw new CannotApplyDiscount('Discount cannot be applied.');
        }

        // Since the products are offered as free, make sure each item has a 0,00 price
        foreach ($this->adjuster['free_items'] as $item) {

            $discountAmount = $item->discountBasePrice();

            $eligibleForDiscount->addToDiscountTotal($discountAmount);
            $eligibleForDiscount->addDiscount(new AppliedDiscount(
                $this->id,
                TypeKey::fromDiscount($this)->get(),
                $discountAmount,
                Cash::from($discountAmount)->asPercentage($eligibleForDiscount->discountBasePrice(), 0),
                $this->data
            ));

            // Add free item to order
            $order->items()->add($item);
        }
    }

    public function discountAmount(Order $order, EligibleForDiscount $eligibleForDiscount): Money
    {
        return $eligibleForDiscount->discountBasePrice();
    }

    /**
     * @param array $conditions
     * @param array $adjusters
     */
    protected function validateParameters(array $conditions, array $adjusters)
    {
        parent::validateParameters($conditions, $adjusters);

        if (!isset($adjusters['free_items'])) {
            throw new \InvalidArgumentException('Missing adjuster value \'free_items\', required for discount '.get_class($this));
        }

        if (!is_array($adjusters['free_items'])) {
            throw new \InvalidArgumentException('Invalid adjuster value \'free_items\' for discount '.get_class($this).'. Array is expected.');
        }

        Assertion::allIsInstanceOf($adjusters['free_items'], Item::class);
    }
}
