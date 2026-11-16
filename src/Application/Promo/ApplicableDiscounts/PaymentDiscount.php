<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts;

use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount\AppliedDiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;

interface PaymentDiscount extends ApplicableDiscount
{
    public function isApplicable(Order $order, Payment $payment): bool;

    public function getDiscountPrice(Order $order, Payment $payment): DiscountPrice;

    public function apply(Order $order, Payment $payment, AppliedDiscountId $appliedDiscountId): void;
}
