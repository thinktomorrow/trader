<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Line;

use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

final class Line implements ChildEntity
{
    public readonly OrderId $orderId;
    public readonly LineId $lineId;
    private VariantId $variantId;
    private LinePrice $linePrice;
    private Quantity $quantity;

    private function __construct()
    {

    }

    public static function create(OrderId $orderId, LineId $lineId, VariantId $productId, LinePrice $linePrice, Quantity $quantity): static
    {
        $line = new static();

        $line->orderId = $orderId;
        $line->lineId = $lineId;
        $line->variantId = $productId;
        $line->linePrice = $linePrice;
        $line->quantity = $quantity;

        return $line;
    }

    public function updatePrice(LinePrice $linePrice): void
    {
        $this->linePrice = $linePrice;
    }

    public function updateQuantity(Quantity $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getVariantId(): VariantId
    {
        return $this->variantId;
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
            'line_id' => $this->lineId->get(),
            'variant_id' => $this->variantId->get(),
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
        $line->lineId = LineId::fromString($state['line_id']);
        $line->variantId = VariantId::fromString($state['variant_id']);
        $line->linePrice = LinePrice::fromScalars($state['line_price'], 'EUR', $state['tax_rate'], $state['includes_vat']);
        $line->quantity = Quantity::fromInt($state['quantity']);

        return $line;
    }
}
