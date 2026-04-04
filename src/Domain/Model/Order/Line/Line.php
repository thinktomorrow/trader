<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Line;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildAggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\ItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\ItemPrice;
use Thinktomorrow\Trader\Domain\Common\Price\WithAuthoritativeIncl;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Common\Vat\VatRoundingStrategy;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableId;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableItem;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\HasDiscounts;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\HasPersonalisations;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisation;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

final class Line implements ChildAggregate, DiscountableItem
{
    use HasData;
    use HasDiscounts;
    use HasPersonalisations;
    use ReducesStock;
    use WithAuthoritativeIncl;

    public readonly OrderId $orderId;

    public readonly LineId $lineId;

    private ?PurchasableReference $purchasableReference;

    private ItemPrice $unitPrice;

    private Quantity $quantity;

    private function __construct() {}

    public static function create(OrderId $orderId, LineId $lineId, PurchasableReference $purchasableReference, ItemPrice $unitPrice, Quantity $quantity, array $data): static
    {
        $line = new self;

        $line->orderId = $orderId;
        $line->lineId = $lineId;
        $line->purchasableReference = $purchasableReference;
        $line->unitPrice = $unitPrice;
        $line->quantity = $quantity;
        $line->data = $data;

        return $line;
    }

    public function updatePrice(ItemPrice $unitPrice): void
    {
        $this->unitPrice = $unitPrice;
    }

    public function getUnitPrice(): ItemPrice
    {
        return $this->unitPrice;
    }

    public function getDiscountedUnitPrice(): ItemPrice
    {
        $unitDiscount = $this->calculateItemDiscountPrice($this->getUnitPrice());

        return $this->unitPrice->applyDiscount($unitDiscount);
    }

    public function getTotal(): ItemPrice
    {
        return $this->multiplyByVatRoundingStrategy($this->getDiscountedUnitPrice());
    }

    public function getSubtotal(): ItemPrice
    {
        return $this->multiplyByVatRoundingStrategy($this->unitPrice);
    }

    public function getDiscountPrice(): DiscountPrice|ItemDiscountPrice
    {
        return $this->calculateItemDiscountPrice($this->getSubtotal());
    }

    public function getDiscountPriceExcl(): Money
    {
        return $this->getDiscountPrice()->getExcludingVat();
    }

    public function getDiscountPriceIncl(): Money
    {
        return $this->getDiscountPrice()->getIncludingVat();
    }

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    public function updateQuantity(Quantity $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getPurchasableReference(): ?PurchasableReference
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

            'unit_price_incl' => $this->unitPrice->getIncludingVat()->getAmount(),
            'unit_price_excl' => $this->unitPrice->getExcludingVat()->getAmount(),
            'total_excl' => $this->getTotal()->getExcludingVat()->getAmount(),
            'total_incl' => $this->getTotal()->getIncludingVat()->getAmount(),
            'total_vat' => $this->getTotal()->getVatTotal()->getAmount(),
            'discount_excl' => $this->getDiscountPriceExcl()->getAmount(),
            'discount_incl' => $this->getDiscountPriceIncl()->getAmount(),

            'tax_rate' => $this->unitPrice->getVatPercentage()->get(),
            'includes_vat' => $includesVat,
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
        $line = new static;

        $line->setAuthoritativeIncl($state['includes_vat']);

        // variant_id is deprecated, but kept for backward compatibility and current carts
        if (isset($state['purchasable_reference'])) {
            $line->purchasableReference = $state['purchasable_reference'] ? PurchasableReference::fromString($state['purchasable_reference']) : null;
        } elseif (isset($state['variant_id'])) {
            $line->purchasableReference = $state['variant_id'] ? PurchasableReference::fromString('variant@'.$state['variant_id']) : null;
        } else {
            // Reference does not exist (anymore)
            $line->purchasableReference = null;
        }

        $line->orderId = OrderId::fromString($aggregateState['order_id']);
        $line->lineId = LineId::fromString($state['line_id']);

        $line->quantity = Quantity::fromInt($state['quantity']);
        $line->reducedFromStock = $state['reduced_from_stock'];
        $line->discounts = array_map(fn ($discountState) => Discount::fromMappedData($discountState, $state), $childEntities[Discount::class]);
        $line->personalisations = array_map(fn ($personalisationState) => LinePersonalisation::fromMappedData($personalisationState, $state), $childEntities[LinePersonalisation::class]);
        $line->data = json_decode($state['data'], true);

        $line->unitPrice = $line->authoritativeIncl()
            ? DefaultItemPrice::fromMoney(Cash::make($state['unit_price_incl']), VatPercentage::fromString($state['tax_rate']), true)
            : DefaultItemPrice::fromMoney(Cash::make($state['unit_price_excl']), VatPercentage::fromString($state['tax_rate']), false);

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

    private function multiplyByVatRoundingStrategy(ItemPrice $itemPrice): ItemPrice
    {
        $quantity = $this->quantity->asInt();

        if (! VatRoundingStrategy::fromStringOrDefault($this->getData('vat_rounding_strategy'))->isLineBased()) {
            return $itemPrice->multiply($quantity);
        }

        if (! $itemPrice->isIncludingVatAuthoritative()) {
            return $itemPrice->multiply($quantity);
        }

        return DefaultItemPrice::fromMoney(
            $itemPrice->getIncludingVat()->multiply($quantity),
            $itemPrice->getVatPercentage(),
            true,
        );
    }
}
