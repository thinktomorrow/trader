<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Events;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\LineNumber;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;

final class LineUpdated
{
    public readonly OrderId $orderId;
    public readonly LineNumber $lineNumber;
    public readonly ProductId $productId;
    public readonly Quantity $quantity;

    public function __construct(OrderId $orderId, LineNumber $lineNumber, ProductId $productId, Quantity $quantity)
    {
        $this->orderId = $orderId;
        $this->lineNumber = $lineNumber;
        $this->productId = $productId;
        $this->quantity = $quantity;
    }
}
