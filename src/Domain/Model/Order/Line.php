<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;

final class Line implements ChildEntity
{
    public readonly OrderId $orderId;
    public readonly LineNumber $lineNumber;
    private ProductId $productId;
    private LinePrice $linePrice;
    private Quantity $quantity;

    private function __construct()
    {

    }

    public static function create(OrderId $orderId, LineNumber $lineNumber, ProductId $productId, LinePrice $linePrice, Quantity $quantity): static
    {
        $line = new static();

        $line->orderId = $orderId;
        $line->lineNumber = $lineNumber;
        $line->productId = $productId;
        $line->linePrice = $linePrice;
        $line->quantity = $quantity;

        return $line;
    }

    public function update(ProductId $productId, LinePrice $linePrice, Quantity $quantity): void
    {
        $this->productId = $productId;
        $this->linePrice = $linePrice;
        $this->quantity = $quantity;
    }

    public function getProductId(): ProductId
    {
        return $this->productId;
    }

    public function getTotal(): Price
    {
        return $this->linePrice
            ->multiply($this->quantity->asInt());
    }

    public function getMappedData(): array
    {
        return [
            'order_id' => $this->orderId->get(),
            'line_number' => $this->lineNumber->asInt(),
            'product_id' => $this->productId->get(),
            'line_price' => $this->linePrice->getMoney()->getAmount(),
            'tax_rate' => $this->linePrice->getTaxRate()->toPercentage()->get(),
            'includes_vat' => $this->linePrice->includesTax(),
            'quantity' => $this->quantity->asInt(),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $line = new static();

        $line->orderId = OrderId::fromString($aggregateState['order_id']);
        $line->lineNumber = LineNumber::fromInt($state['line_number']);
        $line->productId = ProductId::fromString($state['product_id']);
        $line->linePrice = LinePrice::fromScalars($state['line_price'], 'EUR', $state['tax_rate'], $state['includes_vat']);
        $line->quantity = Quantity::fromInt($state['quantity']);

        return $line;
    }
}
