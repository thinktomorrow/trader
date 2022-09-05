<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Discount;

interface Discountable
{
    public function getDiscountableId(): DiscountableId;

    public function getDiscountableType(): DiscountableType;

    /**
     * The total amount of the calculated discount.
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
