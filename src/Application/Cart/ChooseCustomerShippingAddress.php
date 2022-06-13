<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Address\OrderAddressId;

class ChooseCustomerShippingAddress
{
    private string $orderId;
    private string $orderAddressId;

    public function __construct(string $orderId, string $orderAddressId)
    {
        $this->orderId = $orderId;
        $this->orderAddressId = $orderAddressId;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getOrderAddressId(): OrderAddressId
    {
        return OrderAddressId::fromString($this->orderAddressId);
    }
}
