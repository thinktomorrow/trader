<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Shipping;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildAggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultServicePrice;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\ServicePrice;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableId;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableItem;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\HasDiscounts;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;

final class Shipping implements ChildAggregate, DiscountableItem
{
    use HasData;
    use HasDiscounts;

    public readonly OrderId $orderId;
    public readonly ShippingId $shippingId;
    private ?ShippingProfileId $shippingProfileId;
    private ShippingState $shippingState;
    private ServicePrice $shippingCost;

    private function __construct()
    {
    }

    public static function create(OrderId $orderId, ShippingId $shippingId, ShippingProfileId $shippingProfileId, ShippingState $shippingState, ServicePrice $shippingCost): static
    {
        $shipping = new static();

        $shipping->orderId = $orderId;
        $shipping->shippingId = $shippingId;
        $shipping->shippingProfileId = $shippingProfileId;
        $shipping->shippingState = $shippingState;
        $shipping->shippingCost = $shippingCost;

        return $shipping;
    }

    public function updateShippingProfile(ShippingProfileId $shippingProfileId): void
    {
        $this->shippingProfileId = $shippingProfileId;
    }

    public function updateState(ShippingState $shippingState): void
    {
        $this->shippingState = $shippingState;
    }

    public function updateCost(ServicePrice $shippingCost): void
    {
        $this->shippingCost = $shippingCost;
    }

    public function getShippingProfileId(): ShippingProfileId
    {
        return $this->shippingProfileId;
    }

    public function getShippingState(): ShippingState
    {
        return $this->shippingState;
    }

    public function getShippingCost(): ServicePrice
    {
        return $this->shippingCost;
    }

    public function getShippingCostTotal(): ServicePrice
    {
        return $this->shippingCost->applyDiscount($this->getDiscountPrice());
    }

    public function getMappedData(): array
    {
        $data = $this->addDataIfNotNull(['shipping_profile_id' => $this->shippingProfileId?->get()]);

        return [
            'order_id' => $this->orderId->get(),
            'shipping_id' => $this->shippingId->get(),
            'shipping_profile_id' => $this->shippingProfileId?->get(),
            'shipping_state' => $this->shippingState->getValueAsString(),
            // Always store excluding vat cost, vat is calculated based on the products in the order.
            'cost_excl' => $this->shippingCost->getExcludingVat()->getAmount(),
            'discount_excl' => $this->getDiscountPrice()->getExcludingVat()->getAmount(),
            'total_excl' => $this->getShippingCostTotal()->getExcludingVat()->getAmount(),
            'data' => json_encode($data),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            Discount::class => array_map(fn($discount) => $discount->getMappedData(), $this->discounts),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState, array $childEntities = []): static
    {
        $shipping = new static();

        if (!$state['shipping_state'] instanceof ShippingState) {
            throw new \InvalidArgumentException('Shipping state is expected to be instance of ShippingState. Instead ' . gettype($state['shipping_state']) . ' is passed.');
        }

        $shipping->orderId = OrderId::fromString($aggregateState['order_id']);
        $shipping->shippingId = ShippingId::fromString($state['shipping_id']);
        $shipping->shippingProfileId = $state['shipping_profile_id'] ? ShippingProfileId::fromString($state['shipping_profile_id']) : null;
        $shipping->shippingState = $state['shipping_state'];
        $shipping->shippingCost = DefaultServicePrice::fromExcludingVat(Money::EUR($state['cost_excl']));
        $shipping->discounts = array_map(fn($discountState) => Discount::fromMappedData($discountState, $state), $childEntities[Discount::class]);
        $shipping->data = json_decode($state['data'], true);

        return $shipping;
    }

    public function getDiscountPrice(): DiscountPrice
    {
        return $this->calculateDiscountPrice($this->shippingCost->getExcludingVat());
    }

    public function getDiscountableId(): DiscountableId
    {
        return DiscountableId::fromString($this->shippingId->get());
    }

    public function getDiscountableType(): DiscountableType
    {
        return DiscountableType::shipping;
    }
}
