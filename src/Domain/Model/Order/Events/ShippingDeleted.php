<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Events;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingId;

final class ShippingDeleted
{
    public readonly OrderId $orderId;
    public readonly ShippingId $shippingId;

    public function __construct(OrderId $orderId, ShippingId $shippingId)
    {
        $this->orderId = $orderId;
        $this->shippingId = $shippingId;
    }
}
