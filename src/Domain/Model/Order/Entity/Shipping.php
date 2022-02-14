<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Entity;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingTotal;

final class Shipping implements ChildEntity
{
    public readonly OrderId $orderId;
    private ShippingId $shippingId;
    private ShippingState $shippingState;
    private ShippingTotal $shippingTotal;
    private array $data = [];

    private function __construct()
    {
    }

    public static function create(OrderId $orderId, ShippingId $shippingId, ShippingState $shippingState, ShippingTotal $shippingTotal, array $data): static
    {
        $shipping = new static();

        $shipping->orderId = $orderId;
        $shipping->shippingId = $shippingId;
        $shipping->shippingState = $shippingState;
        $shipping->shippingTotal = $shippingTotal;
        $shipping->data = $data;

        return $shipping;
    }

    public function update(ShippingId $shippingId, ShippingState $shippingState, ShippingTotal $shippingTotal, array $data): void
    {
        $this->shippingId = $shippingId;
        $this->shippingState = $shippingState;
        $this->shippingTotal = $shippingTotal;
        $this->data = array_merge($this->data, $data);
    }

    public function getShippingId(): ShippingId
    {
        return $this->shippingId;
    }

    public function getMappedData(): array
    {
        return [
            'shipping_id'    => $this->shippingId->get(),
            'shipping_state' => $this->shippingState->value,
            'shipping_total' => $this->shippingTotal->getMoney(),
            'tax_rate'       => $this->shippingTotal->getTaxRate()->toPercentage(),
            'includes_vat'   => $this->shippingTotal->includesTax(),
            'data'           => $this->data,
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $shipping = new static();

        $shipping->orderId = OrderId::fromString($state['order_id']);
        $shipping->shippingId = ShippingId::fromString($state['shipping_id']);
        $shipping->shippingState = ShippingState::from($state['shipping_state']);
        $shipping->shippingTotal = ShippingTotal::fromScalars(
            $state['shipping_total'], 'EUR', $state['tax_rate'], $state['includes_vat']
        );
        $shipping->data = $state['data'];

        return $shipping;
    }
}
