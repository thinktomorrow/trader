<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order;

use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;

final class CreateOrder
{
    private string $customerId;

    public function __construct(string $customerId)
    {
        $this->customerId = $customerId;
    }

    public function getCustomerId(): CustomerId
    {
        return CustomerId::fromString($this->customerId);
    }
}
