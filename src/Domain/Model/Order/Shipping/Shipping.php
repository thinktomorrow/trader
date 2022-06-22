<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Shipping;

use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceTotal;
use Thinktomorrow\Trader\Domain\Model\Order\HasDiscounts;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discountable;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;

final class Shipping implements ChildEntity, Discountable
{
    use HasData;
    use HasDiscounts;

    public readonly OrderId $orderId;
    public readonly ShippingId $shippingId;
    private ShippingProfileId $shippingProfileId;
    private ShippingState $shippingState;
    private ShippingCost $shippingCost;

    private function __construct()
    {
    }

    public static function create(OrderId $orderId, ShippingId $shippingId, ShippingProfileId $shippingProfileId, ShippingCost $shippingCost): static
    {
        $shipping = new static();

        $shipping->orderId = $orderId;
        $shipping->shippingId = $shippingId;
        $shipping->shippingProfileId = $shippingProfileId;
        $shipping->shippingState = ShippingState::none;
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

    public function getMappedData(): array
    {
        return [
            'order_id' => $this->orderId->get(),
            'shipping_id' => $this->shippingId->get(),
            'shipping_profile_id' => $this->shippingProfileId->get(),
            'shipping_state' => $this->shippingState->value,
            'cost' => $this->shippingCost->getMoney()->getAmount(),
            'tax_rate' => $this->shippingCost->getTaxRate()->toPercentage()->get(),
            'includes_vat' => $this->shippingCost->includesVat(),
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $shipping = new static();

        $shipping->orderId = OrderId::fromString($aggregateState['order_id']);
        $shipping->shippingId = ShippingId::fromString($state['shipping_id']);
        $shipping->shippingProfileId = ShippingProfileId::fromString($state['shipping_profile_id']);
        $shipping->shippingState = ShippingState::from($state['shipping_state']);
        $shipping->shippingCost = ShippingCost::fromScalars(
            $state['cost'],
            $state['tax_rate'],
            $state['includes_vat']
        );
        $shipping->data = json_decode($state['data'], true);

        return $shipping;
    }

    public function getDiscountableTotal(array $conditions): Price|PriceTotal
    {
        return $this->getShippingCost();
    }

    public function getDiscountableQuantity(array $conditions): Quantity
    {
        return Quantity::fromInt(1);
    }

    public function getDiscountTotal(): DiscountTotal
    {
        return $this->calculateDiscountTotal($this->getShippingCost());
    }
}
