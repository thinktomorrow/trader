<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Events;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\LineNumber;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;

final class LineDeleted
{
    public readonly OrderId $orderId;
    public readonly LineNumber $lineNumber;
    public readonly ProductId $productId;

    public function __construct(OrderId $orderId, LineNumber $lineNumber, ProductId $productId)
    {
        $this->orderId = $orderId;
        $this->lineNumber = $lineNumber;
        $this->productId = $productId;
    }
}
