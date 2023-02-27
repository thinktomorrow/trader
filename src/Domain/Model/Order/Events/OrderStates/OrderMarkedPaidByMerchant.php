<?php

namespace Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderState;

class OrderMarkedPaidByMerchant
{
    public function __construct(
        public readonly OrderId    $orderId,
        public readonly OrderState $oldState,
        public readonly OrderState $newState,
        public readonly array      $data,
    ) {
    }
}
