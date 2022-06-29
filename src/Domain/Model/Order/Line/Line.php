<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Line;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceTotal;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildAggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discountable;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableId;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\HasDiscounts;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

final class Line implements ChildAggregate, Discountable
{
    use HasData;
    use HasDiscounts;

    public readonly OrderId $orderId;
    public readonly LineId $lineId;
    private VariantId $variantId;
    private LinePrice $linePrice;
    private Quantity $quantity;

    private function __construct()
    {
    }

    public static function create(OrderId $orderId, LineId $lineId, VariantId $productId, LinePrice $linePrice, Quantity $quantity, array $data): static
    {
        $line = new static();

        $line->orderId = $orderId;
        $line->lineId = $lineId;
        $line->variantId = $productId;
        $line->linePrice = $linePrice;
        $line->quantity = $quantity;
        $line->data = $data;

        return $line;
    }

    public function updatePrice(LinePrice $linePrice): void
    {
        $this->linePrice = $linePrice;
    }

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    public function updateQuantity(Quantity $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getVariantId(): VariantId
    {
        return $this->variantId;
    }

    public function getLinePrice(): Price
    {
        return $this->linePrice;
    }

    public function getSubTotal(): Price
    {
        return $this->linePrice
            ->multiply($this->quantity->asInt());
    }

    public function getTotal(): Price
    {
        return $this->getSubTotal()
            ->subtract($this->getDiscountTotal());
    }

    public function getTaxTotal(): Money
    {
        return $this->getTotal()->getIncludingVat()->subtract(
            $this->getTotal()->getExcludingVat()
        );
    }

    public function getDiscountTotal(): DiscountTotal
    {
        return $this->calculateDiscountTotal($this->getLinePrice());
    }

    public function getMappedData(): array
    {
        return [
            'order_id' => $this->orderId->get(),
            'line_id' => $this->lineId->get(),
            'variant_id' => $this->variantId->get(),
            'line_price' => $this->linePrice->getMoney()->getAmount(),
            'tax_rate' => $this->linePrice->getTaxRate()->toPercentage()->get(),
            'includes_vat' => $this->linePrice->includesVat(),
            'total' => $this->getTotal()->getMoney()->getAmount(),
            'tax_total' => $this->getTaxTotal()->getAmount(),
            'discount_total' => $this->getTotal()->includesVat()
                ? $this->getDiscountTotal()->getIncludingVat()->getAmount()
                : $this->getDiscountTotal()->getExcludingVat()->getAmount(),
            'quantity' => $this->quantity->asInt(),
            'data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            Discount::class => array_map(fn ($discount) => $discount->getMappedData(), $this->discounts),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState, array $childEntities = []): static
    {
        $line = new static();

        $line->orderId = OrderId::fromString($aggregateState['order_id']);
        $line->lineId = LineId::fromString($state['line_id']);
        $line->variantId = VariantId::fromString($state['variant_id']);
        $line->linePrice = LinePrice::fromScalars($state['line_price'], $state['tax_rate'], $state['includes_vat']);
        $line->quantity = Quantity::fromInt($state['quantity']);
        $line->discounts = array_map(fn ($discountState) => Discount::fromMappedData($discountState, $state), $childEntities[Discount::class]);
        $line->data = json_decode($state['data'], true);

        return $line;
    }

    public function getDiscountableTotal(array $conditions): Price|PriceTotal
    {
        return $this->getSubTotal();
    }

    public function getDiscountableQuantity(array $conditions): Quantity
    {
        return $this->quantity;
    }

    public function getDiscountableId(): DiscountableId
    {
        return DiscountableId::fromString($this->lineId->get());
    }

    public function getDiscountableType(): DiscountableType
    {
        return DiscountableType::line;
    }

    public function deleteDiscounts(): void
    {
        $this->discounts = [];
    }
}
