<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\State\Shipping;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;

trait HasShippingStateValues
{
    private string $orderId;
    private string $shippingId;
    private array $data;

    public function __construct(string $orderId, string $shippingId, array $data = [])
    {
        $this->orderId = $orderId;
        $this->shippingId = $shippingId;
        $this->data = $data;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getShippingId(): ShippingId
    {
        return ShippingId::fromString($this->shippingId);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
