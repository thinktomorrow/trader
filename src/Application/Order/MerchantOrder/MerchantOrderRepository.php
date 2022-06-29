<?php

namespace Thinktomorrow\Trader\Application\Order\MerchantOrder;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

interface MerchantOrderRepository
{
    public function findMerchantOrder(OrderId $orderId): MerchantOrder;
}
