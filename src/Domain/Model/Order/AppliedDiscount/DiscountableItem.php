<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount;

use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\ItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;

interface DiscountableItem
{
    public function getDiscountableId(): DiscountableId;

    public function getDiscountableType(): DiscountableType;

    /**
     * Total sum of all discount prices applied on this item
     */
    public function getDiscountPrice(): DiscountPrice|ItemDiscountPrice;

    /**
     * @return Discount[]
     */
    public function getDiscounts(): array;

    public function addDiscount(Discount $discount): void;

    public function deleteDiscount(AppliedDiscountId $discountId): void;

    public function deleteDiscounts(): void;
}
