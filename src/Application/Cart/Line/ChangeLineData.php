<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\Line;

use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

final class ChangeLineData
{
    private string $orderId;
    private string $lineId;
    private array $data;

    public function __construct(string $orderId, string $lineId, array $data)
    {
        $this->orderId = $orderId;
        $this->lineId = $lineId;
        $this->data = $data;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getLineId(): LineId
    {
        return LineId::fromString($this->lineId);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
