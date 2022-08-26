<?php

namespace Thinktomorrow\Trader\Application\Order\MerchantOrder;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;

interface MerchantOrderRepository
{
    public function findMerchantOrder(OrderId $orderId): MerchantOrder;

    public function findMerchantOrderByReference(OrderReference $orderReference): MerchantOrder;
}
