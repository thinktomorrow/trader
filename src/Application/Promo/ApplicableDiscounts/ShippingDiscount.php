<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts;

use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount\AppliedDiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;

interface ShippingDiscount extends ApplicableDiscount
{
    public function isApplicable(Order $order, Shipping $shipping): bool;

    public function getDiscountPrice(Order $order, Shipping $shipping): DiscountPrice;

    public function apply(Order $order, Shipping $shipping, AppliedDiscountId $appliedDiscountId): void;
}
