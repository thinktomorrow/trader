<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts;

use Thinktomorrow\Trader\Domain\Common\Map\Mappable;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;

interface ApplicableDiscount extends Mappable
{
    public function getDiscountableType(): DiscountableType;

    /**
     * Whether this discount could affect any combination evaluated for the order.
     * Unlike isApplicable(), which checks one target in its current state, this
     * pre-check returns false only when no combination could make it apply.
     * When in doubt, keep the discount as a candidate.
     */
    public function couldApply(Order $order): bool;
}
