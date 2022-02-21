<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Events;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

final class LineDeleted
{
    public readonly OrderId $orderId;
    public readonly LineId $lineId;
    public readonly VariantId $productId;

    public function __construct(OrderId $orderId, LineId $lineId, VariantId $productId)
    {
        $this->orderId = $orderId;
        $this->lineId = $lineId;
        $this->productId = $productId;
    }
}
