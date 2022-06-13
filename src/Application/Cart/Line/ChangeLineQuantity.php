<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\Line;

use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

final class ChangeLineQuantity
{
    private string $orderId;
    private string $lineId;
    private int $quantity;

    public function __construct(string $orderId, string $lineId, int $quantity)
    {
        $this->orderId = $orderId;
        $this->lineId = $lineId;
        $this->quantity = $quantity;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getLineId(): LineId
    {
        return LineId::fromString($this->lineId);
    }

    public function getQuantity(): Quantity
    {
        return Quantity::fromInt($this->quantity);
    }
}
