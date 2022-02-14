<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Details;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

interface OrderDetailsRepository
{
    public function find(OrderId $orderId): OrderDetails;
}
