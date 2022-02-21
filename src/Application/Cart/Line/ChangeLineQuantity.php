<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\Line;


use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;

final class ChangeLineQuantity
{
    private string $orderId;
    private int $lineId;
    private int $quantity;

    public function __construct(string $orderId, int $lineId, int $quantity)
    {
        $this->orderId = $orderId;
        $this->lineId = $lineId;
        $this->quantity = $quantity;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getlineId(): LineId
    {
        // TODO: get read model of order in order to get info on lines count so we can get the next lineId
        return LineId::fromInt($this->lineId);
    }

    public function getQuantity(): Quantity
    {
        return Quantity::fromInt($this->quantity);
    }
}
