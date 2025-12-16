<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Line;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildAggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\ItemPrice;
use Thinktomorrow\Trader\Domain\Common\Price\WithPriceInputMode;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableId;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableItem;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\GetValidatedTotalDiscountPrice;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\HasDiscounts;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\HasPersonalisations;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisation;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

final class Line implements ChildAggregate, DiscountableItem
{
    use WithPriceInputMode;
    use HasData;
    use HasDiscounts;
    use HasPersonalisations;
    use ReducesStock;

    public readonly OrderId $orderId;
    public readonly LineId $lineId;
    private ?VariantId $variantId;
    private ItemPrice $linePrice;
    private Quantity $quantity;

    private function __construct()
    {
    }

    public static function create(OrderId $orderId, LineId $lineId, VariantId $productId, ItemPrice $linePrice, Quantity $quantity, array $data): static
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

    public function updatePrice(ItemPrice $linePrice): void
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

    public function getLinePrice(): ItemPrice
    {
        return $this->linePrice;
    }

    public function getSubTotal(): ItemPrice
    {
        return $this->linePrice->multiply($this->quantity->asInt());
    }

    public function getTotal(): DefaultItemPrice
    {
        return $this->getSubTotal()
            ->applyDiscount($this->getTotalDiscountPrice());
    }

    public function getTotalDiscountPrice(): DiscountPrice
    {
        return GetValidatedTotalDiscountPrice::get($this->linePrice, $this);
    }

    public function getTaxTotal(): Money
    {
        return $this->getTotal()->getVatTotal();
    }

    public function getMappedData(): array
    {
        $data = $this->addDataIfNotNull(['variant_id' => $this->variantId?->get()]);

        $includesVat = $this->priceEnteredIncludesVat();

        return [
            'order_id' => $this->orderId->get(),
            'line_id' => $this->lineId->get(),
            'variant_id' => $this->variantId?->get(),
            'line_price' => $includesVat
                ? $this->linePrice->getIncludingVat()->getAmount()
                : $this->linePrice->getExcludingVat()->getAmount(),
            'tax_rate' => $this->linePrice->getVatPercentage()->get(),
            'includes_vat' => $includesVat,
            'total' => $this->getTotal()->getExcludingVat()->getAmount(),
            'tax_total' => $this->getTaxTotal()->getAmount(),
            'discount_total' => $this->getTotalDiscountPrice()->getExcludingVat()->getAmount(),
            'quantity' => $this->quantity->asInt(),
            'reduced_from_stock' => $this->reducedFromStock,
            'data' => json_encode($data),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            LinePersonalisation::class => array_map(fn ($personalisation) => $personalisation->getMappedData(), $this->personalisations),
            Discount::class => array_map(fn ($discount) => $discount->getMappedData(), $this->discounts),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState, array $childEntities = []): static
    {
        $line = new static();

        $line->setPriceEnteredIncludingVat($state['includes_vat']);

        $line->orderId = OrderId::fromString($aggregateState['order_id']);
        $line->lineId = LineId::fromString($state['line_id']);
        $line->variantId = $state['variant_id'] ? VariantId::fromString($state['variant_id']) : null;
        $line->linePrice = DefaultItemPrice::fromMoney(Cash::make($state['line_price']), VatPercentage::fromString($state['tax_rate']), $state['includes_vat']);
        $line->quantity = Quantity::fromInt($state['quantity']);
        $line->reducedFromStock = $state['reduced_from_stock'];
        $line->discounts = array_map(fn ($discountState) => Discount::fromMappedData($discountState, $state), $childEntities[Discount::class]);
        $line->personalisations = array_map(fn ($personalisationState) => LinePersonalisation::fromMappedData($personalisationState, $state), $childEntities[LinePersonalisation::class]);
        $line->data = json_decode($state['data'], true);

        return $line;
    }

    public function getDiscountableId(): DiscountableId
    {
        return DiscountableId::fromString($this->lineId->get());
    }

    public function getDiscountableType(): DiscountableType
    {
        return DiscountableType::line;
    }
}
