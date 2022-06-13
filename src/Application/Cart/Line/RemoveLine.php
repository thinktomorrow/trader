<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\Line;

use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

final class RemoveLine
{
    private string $orderId;
    private string $lineId;

    public function __construct(string $orderId, string $lineId)
    {
        $this->orderId = $orderId;
        $this->lineId = $lineId;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getLineId(): LineId
    {
        return LineId::fromString($this->lineId);
    }
}
