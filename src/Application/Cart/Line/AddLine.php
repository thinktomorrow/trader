<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\Line;


use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

final class AddLine
{
    private string $orderId;
    private string $lineId;
    private string $variantId;
    private int $quantity;

    public function __construct(string $orderId, string $lineId, string $variantId, int $quantity)
    {
        $this->orderId = $orderId;
        $this->lineId = $lineId;
        $this->variantId = $variantId;
        $this->quantity = $quantity;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getLineId(): LineId
    {
        // TODO: get read model of order in order to get info on lines count so we can get the next lineId
        return LineId::fromString($this->lineId);
    }

    public function getVariantId(): VariantId
    {
        return VariantId::fromString($this->variantId);
    }

    public function getQuantity(): Quantity
    {
        return Quantity::fromInt($this->quantity);
    }
}
