<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\Coupon;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

class RemoveCoupon
{
    private string $orderId;

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }
}
