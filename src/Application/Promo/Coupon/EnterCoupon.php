<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\Coupon;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

final class EnterCoupon
{
    private string $orderId;
    private string $couponCode;

    public function __construct(string $orderId, string $couponCode)
    {
        $this->orderId = $orderId;
        $this->couponCode = $couponCode;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getCouponCode(): string
    {
        return $this->couponCode;
    }
}
