<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Events;

use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

final class LineUpdated
{
    public readonly OrderId $orderId;
    public readonly LineId $lineId;

    public function __construct(OrderId $orderId, LineId $lineId)
    {
        $this->orderId = $orderId;
        $this->lineId = $lineId;
    }
}
