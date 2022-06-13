<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

final class ChooseCustomer
{
    private string $orderId;
    private string $customerId;

    public function __construct(string $orderId, string $customerId)
    {
        $this->orderId = $orderId;
        $this->customerId = $customerId;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getCustomerId(): CustomerId
    {
        return CustomerId::fromString($this->customerId);
    }
}
