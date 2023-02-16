<?php

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustLine;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

class DefaultAdjustLine implements AdjustLine
{
    public function adjust(Order $order, Line $line): void
    {
        return;
    }
}
