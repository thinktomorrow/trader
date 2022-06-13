<?php

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountId;

trait HasDiscounts
{
    private array $discounts = [];

    public function addDiscount(Discount $discount): void
    {
        // TODO:: assert order id matches
        // TODO: assert discount isnt already added... (cf. addShipping)

        if (! in_array($discount, $this->discounts)) {
            $this->discounts[] = $discount;
        }
    }

    public function deleteDiscount(DiscountId $discountId): void
    {
        /** @var Discount $existingDiscount */
        foreach ($this->discounts as $indexToBeDeleted => $existingDiscount) {
            if ($existingDiscount->discountId->equals($discountId)) {
                unset($this->discounts[$indexToBeDeleted]);
            }
        }
    }
}
