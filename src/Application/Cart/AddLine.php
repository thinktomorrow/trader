<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;


use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\LineNumber;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;

final class AddLine
{
    private string $orderId;
    private int $lineNumber;
    private string $productId;
    private int $quantity;

    public function __construct(string $orderId, int $lineNumber, string $productId, int $quantity)
    {
        $this->orderId = $orderId;
        $this->lineNumber = $lineNumber;
        $this->productId = $productId;
        $this->quantity = $quantity;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getLineNumber(): LineNumber
    {
        // TODO: get read model of order in order to get info on lines count so we can get the next lineNumber
        return LineNumber::fromInt($this->lineNumber);
    }

    public function getProductId(): ProductId
    {
        return ProductId::fromString($this->productId);
    }

    public function getQuantity(): Quantity
    {
        return Quantity::fromInt($this->quantity);
    }
}
