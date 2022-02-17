<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Shipping;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;

final class Shipping implements ChildEntity
{
    use HasData;

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
            'order_id'            => $this->orderId->get(),
            'shipping_id'         => $this->shippingId->get(),
            'shipping_profile_id' => $this->shippingProfileId->get(),
            'shipping_state'      => $this->shippingState->value,
            'shipping_cost'       => $this->shippingCost->getMoney()->getAmount(),
            'tax_rate'            => $this->shippingCost->getTaxRate()->toPercentage()->get(),
            'includes_vat'        => $this->shippingCost->includesTax(),
            'data'                => $this->data,
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
            $state['shipping_cost'], 'EUR', $state['tax_rate'], $state['includes_vat']
        );
        $shipping->data = $state['data'];

        return $shipping;
    }
}
