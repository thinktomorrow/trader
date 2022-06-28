<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Discount;

use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;

interface Discountable
{
    public function getDiscountableId(): DiscountableId;

    public function getDiscountableType(): DiscountableType;

    /**
     * Total amount on which the discounts should be calculated.
     *
     * @param array $conditions
     */
    public function getDiscountableTotal(array $conditions): Price|PriceTotal;

    /**
     * Quantity of all whitelisted items. Used by quantity specific
     * discount conditions such as MinimumItems.
     *
     * @param array $conditions
     */
    public function getDiscountableQuantity(array $conditions): Quantity;

    /**
     * The total amount of the calculated discount.
     *
     * @return DiscountTotal
     */
    public function getDiscountTotal(): DiscountTotal;

    /**
     * @return Discount[]
     */
    public function getDiscounts(): array;

    public function addDiscount(Discount $discount): void;

    public function deleteDiscount(DiscountId $discountId): void;

    public function deleteDiscounts(): void;
}
