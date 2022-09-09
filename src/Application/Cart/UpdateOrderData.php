<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

class UpdateOrderData
{
    private string $orderId;
    private array $data;

    public function __construct(string $orderId, array $data)
    {
        $this->orderId = $orderId;
        $this->data = $data;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
