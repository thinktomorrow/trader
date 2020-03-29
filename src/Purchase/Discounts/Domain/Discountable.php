<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Discounts\Domain;

use Money\Money;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\AppliedDiscount;

interface Discountable
{
    /**
     * Total amount on which the discounts should be calculated.
     *
     * @param array $conditions
     * @return Money
     */
    public function discountableTotal(array $conditions): Money;

    /**
     * Quantity of all whitelisted items. Used by quantity specific
     * discount conditions such as MinimumItems.
     *
     * @param array $conditions
     * @return int
     */
    public function discountableQuantity(array $conditions): int;

    public function discountTotal(): Money;

    /**
     * @return AppliedDiscount[]
     */
    public function discounts(): array;

    public function addDiscount(AppliedDiscount $discount);
}
