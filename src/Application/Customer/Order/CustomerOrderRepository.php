<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Customer\Order;

use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrder;

interface CustomerOrderRepository
{
    /** @return MerchantOrder[] */
    public function getOrdersByCustomer(CustomerId $customerId): array;
}
