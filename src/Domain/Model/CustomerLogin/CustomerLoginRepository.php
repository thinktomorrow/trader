<?php

namespace Thinktomorrow\Trader\Domain\Model\CustomerLogin;

use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;

interface CustomerLoginRepository
{
    public function save(CustomerLogin $customerLogin): void;

    public function find(CustomerId $customerId): CustomerLogin;
}
