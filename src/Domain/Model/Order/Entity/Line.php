<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Entity;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\LineNumber;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;

final class Line
{
    public readonly OrderId $orderId;
    public readonly LineNumber $lineNumber;
    private ProductId $productId;
    private Quantity $quantity;

    private function __construct()
    {

    }

    public static function create(OrderId $orderId, LineNumber $lineNumber, ProductId $productId, Quantity $quantity): static
    {
        $line = new static();

        $line->orderId = $orderId;
        $line->lineNumber = $lineNumber;
        $line->productId = $productId;
        $line->quantity = $quantity;

        return $line;
    }

    public function update(ProductId $productId, Quantity $quantity): void
    {
        $this->productId = $productId;
        $this->quantity = $quantity;
    }

    public function getProductId(): ProductId
    {
        return $this->productId;
    }

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }
}
