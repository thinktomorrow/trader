<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Events;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;

class ShippingStateUpdated
{
    public function __construct(
        public readonly OrderId              $orderId,
        public readonly ShippingId           $shippingId,
        public readonly ShippingState $formerShippingState,
        public readonly ShippingState $newShippingState
    ) {
    }
}
