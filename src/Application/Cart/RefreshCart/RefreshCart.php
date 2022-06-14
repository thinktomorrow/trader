<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

class RefreshCart
{
    private string $orderId;

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }
}
