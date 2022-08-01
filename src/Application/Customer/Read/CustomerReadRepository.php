<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Customer\Read;

use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;

interface CustomerReadRepository
{
    public function findCustomer(CustomerId $customerId): CustomerRead;
}
