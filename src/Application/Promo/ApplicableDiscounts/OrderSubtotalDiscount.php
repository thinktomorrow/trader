<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts;

use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount\AppliedDiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

interface OrderSubtotalDiscount extends ApplicableDiscount
{
    public function isApplicable(Order $order, Order $target): bool;

    public function getDiscountPrice(Order $order, Order $target): DiscountPrice;

    public function apply(Order $order, Order $target, AppliedDiscountId $appliedDiscountId): void;
}
