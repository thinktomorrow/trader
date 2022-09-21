<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Events;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

final class OrderBillingAddressUpdated
{
    public readonly OrderId $orderId;

    public function __construct(OrderId $orderId)
    {
        $this->orderId = $orderId;
    }
}
