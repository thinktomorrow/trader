<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Discount;

use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;

interface DiscountableItem
{
    public function getDiscountableId(): DiscountableId;

    public function getDiscountableType(): DiscountableType;

    /**
     * Total sum of all discount prices applied on this item
     */
    public function getDiscountPrice(): DiscountPrice;

    /**
     * @return Discount[]
     */
    public function getDiscounts(): array;

    public function addDiscount(Discount $discount): void;

    public function deleteDiscount(DiscountId $discountId): void;

    public function deleteDiscounts(): void;
}
