<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingId;

final class ChooseShipping
{
    private string $orderId;
    private string $shippingId;

    public function __construct(string $orderId, string $shippingId)
    {
        $this->orderId = $orderId;
        $this->shippingId = $shippingId;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getShippingId(): ShippingId
    {
        return ShippingId::fromString($this->shippingId);
    }
}
