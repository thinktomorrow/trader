<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Customer\Order;

use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrder;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;

interface CustomerOrderRepository
{
    /** @return MerchantOrder[] */
    public function getOrdersByCustomer(CustomerId $customerId): array;
}
