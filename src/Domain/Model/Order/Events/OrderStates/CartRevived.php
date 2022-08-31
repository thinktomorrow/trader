<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderState;

final class CartRevived
{
    public function __construct(public readonly OrderId $orderId, public readonly OrderState $oldState, public readonly OrderState $newState)
    {
    }
}
