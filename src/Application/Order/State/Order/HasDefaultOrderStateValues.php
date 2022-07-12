<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\State\Order;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

trait HasDefaultOrderStateValues
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
