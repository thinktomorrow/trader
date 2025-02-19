<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Shipping;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildAggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discountable;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableId;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\HasDiscounts;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;

final class Shipping implements ChildAggregate, Discountable
{
    use HasData;
    use HasDiscounts;

    public readonly OrderId $orderId;
    public readonly ShippingId $shippingId;
    private ?ShippingProfileId $shippingProfileId;
    private ShippingState $shippingState;
    private ShippingCost $shippingCost;

    private function __construct()
    {
    }

    public static function create(OrderId $orderId, ShippingId $shippingId, ShippingProfileId $shippingProfileId, ShippingState $shippingState, ShippingCost $shippingCost): static
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

    public function updateCost(ShippingCost $shippingCost): void
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

    public function getShippingCost(): ShippingCost
    {
        return $this->shippingCost;
    }

    public function getShippingCostTotal(): ShippingCost
    {
        return $this->shippingCost->subtract($this->getDiscountTotal());
    }

    public function getMappedData(): array
    {
        $data = $this->addDataIfNotNull(['shipping_profile_id' => $this->shippingProfileId?->get()]);

        return [
            'order_id' => $this->orderId->get(),
            'shipping_id' => $this->shippingId->get(),
            'shipping_profile_id' => $this->shippingProfileId?->get(),
            'shipping_state' => $this->shippingState->value,
            'cost' => $this->shippingCost->getMoney()->getAmount(),
            'tax_rate' => $this->shippingCost->getVatPercentage()->get(),
            'includes_vat' => $this->shippingCost->includesVat(),
            'data' => json_encode($data),
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
        $shipping = new static();

        if (! $state['shipping_state'] instanceof ShippingState) {
            throw new \InvalidArgumentException('Shipping state is expected to be instance of ShippingState. Instead ' . gettype($state['shipping_state']) . ' is passed.');
        }

        $shipping->orderId = OrderId::fromString($aggregateState['order_id']);
        $shipping->shippingId = ShippingId::fromString($state['shipping_id']);
        $shipping->shippingProfileId = $state['shipping_profile_id'] ? ShippingProfileId::fromString($state['shipping_profile_id']) : null;
        $shipping->shippingState = $state['shipping_state'];
        $shipping->shippingCost = ShippingCost::fromScalars(
            $state['cost'],
            $state['tax_rate'],
            $state['includes_vat']
        );
        $shipping->discounts = array_map(fn ($discountState) => Discount::fromMappedData($discountState, $state), $childEntities[Discount::class]);
        $shipping->data = json_decode($state['data'], true);

        return $shipping;
    }

    public function getDiscountTotal(): DiscountTotal
    {
        return $this->calculateDiscountTotal($this->getShippingCost());
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
