<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Discounts\Domain;

use Money\Money;

interface Discountable
{
    /**
     * Total amount on which the discounts should be calculated.
     *
     * @param array $conditions
     * @return Money
     */
    public function getDiscountableTotal(array $conditions): Money;

    /**
     * Quantity of all whitelisted items. Used by quantity specific
     * discount conditions such as MinimumItems.
     *
     * @param array $conditions
     * @return int
     */
    public function getDiscountableQuantity(array $conditions): int;

    public function getDiscountTotal(): Money;

    public function getDiscounts(): AppliedDiscountCollection;

    public function addDiscount(AppliedDiscount $discount);
}
