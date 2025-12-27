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
use Thinktomorrow\Trader\Domain\Common\Price\WithAuthoritativeIncl;
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

final class Line implements ChildAggregate, DiscountableItem
{
    use WithAuthoritativeIncl;
    use HasData;
    use HasDiscounts;
    use HasPersonalisations;
    use ReducesStock;

    public readonly OrderId $orderId;
    public readonly LineId $lineId;
    private PurchasableReference $purchasableReference;
    private ItemPrice $linePrice;
    private Quantity $quantity;

    private function __construct()
    {
    }

    public static function create(OrderId $orderId, LineId $lineId, PurchasableReference $purchasableReference, ItemPrice $linePrice, Quantity $quantity, array $data): static
    {
        $line = new static();

        $line->orderId = $orderId;
        $line->lineId = $lineId;
        $line->purchasableReference = $purchasableReference;
        $line->linePrice = $linePrice; // unit price
        $line->quantity = $quantity;
        $line->data = $data;

        return $line;
    }

    public function updatePrice(ItemPrice $unitPrice): void
    {
        $this->linePrice = $unitPrice;
    }

    public function getLinePrice(): ItemPrice
    {
        return $this->linePrice;
    }

    public function getSubTotal(): ItemPrice
    {
        return $this->linePrice->multiply($this->quantity->asInt());
    }

    public function getTotal(): ItemPrice
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

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    public function updateQuantity(Quantity $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getPurchasableReference(): PurchasableReference
    {
        return $this->purchasableReference;
    }

    public function getMappedData(): array
    {
        // Lines with removed purchasable (e.g. deleted product) should still be stored,
        // so only add purchasable_reference when not null.
        $purchasableReference = isset($this->purchasableReference) ? $this->purchasableReference->get() : null;

        $data = $this->addDataIfNotNull(['purchasable_reference' => $purchasableReference]);

        $includesVat = $this->authoritativeIncl();

        return [
            'order_id' => $this->orderId->get(),
            'line_id' => $this->lineId->get(),
            'purchasable_reference' => $purchasableReference,

            'unit_price_incl' => $this->linePrice->getIncludingVat()->getAmount(),
            'unit_price_excl' => $this->linePrice->getExcludingVat()->getAmount(),
            'total_excl' => $this->getTotal()->getExcludingVat()->getAmount(),
            'total_incl' => $this->getTotal()->getIncludingVat()->getAmount(),
            'total_vat' => $this->getTotal()->getVatTotal()->getAmount(),
            'discount_excl' => $this->getTotalDiscountPrice()->getExcludingVat()->getAmount(),

            'tax_rate' => $this->linePrice->getVatPercentage()->get(),
            'includes_vat' => $includesVat,
//            'total' => $this->getTotal()->getExcludingVat()->getAmount(),
//            'tax_total' => $this->getTaxTotal()->getAmount(),
//            'discount_total' => $this->getTotalDiscountPrice()->getExcludingVat()->getAmount(),
            'quantity' => $this->quantity->asInt(),
            'reduced_from_stock' => $this->reducedFromStock,
            'data' => json_encode($data),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            LinePersonalisation::class => array_map(fn($personalisation) => $personalisation->getMappedData(), $this->personalisations),
            Discount::class => array_map(fn($discount) => $discount->getMappedData(), $this->discounts),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState, array $childEntities = []): static
    {
        $line = new static();

        $line->setAuthoritativeIncl($state['includes_vat']);

        // variant_id is deprecated, but kept for backward compatibility and current carts
        if (isset($state['purchasable_reference'])) {
            $line->purchasableReference = $state['purchasable_reference'] ? PurchasableReference::fromString($state['purchasable_reference']) : null;
        } elseif (isset($state['variant_id'])) {
            $line->purchasableReference = $state['variant_id'] ? PurchasableReference::fromString('variant@' . $state['variant_id']) : null;
        }

        $line->orderId = OrderId::fromString($aggregateState['order_id']);
        $line->lineId = LineId::fromString($state['line_id']);

        $line->linePrice = $line->authoritativeIncl()
            ? DefaultItemPrice::fromMoney(Cash::make($state['unit_price_incl']), VatPercentage::fromString($state['tax_rate']), true)
            : DefaultItemPrice::fromMoney(Cash::make($state['unit_price_excl']), VatPercentage::fromString($state['tax_rate']), false);

        $line->quantity = Quantity::fromInt($state['quantity']);
        $line->reducedFromStock = $state['reduced_from_stock'];
        $line->discounts = array_map(fn($discountState) => Discount::fromMappedData($discountState, $state), $childEntities[Discount::class]);
        $line->personalisations = array_map(fn($personalisationState) => LinePersonalisation::fromMappedData($personalisationState, $state), $childEntities[LinePersonalisation::class]);
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
