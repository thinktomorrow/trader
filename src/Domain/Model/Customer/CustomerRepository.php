<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Customer;

use Thinktomorrow\Trader\Domain\Common\Email;

interface CustomerRepository
{
    public function save(Customer $customer): void;

    public function find(CustomerId $customerId): Customer;

    public function findByEmail(Email $email): Customer;

    public function existsByEmail(Email $email, ?CustomerId $ignoredCustomerId = null): bool;

    public function delete(CustomerId $customerId): void;

    public function nextReference(): CustomerId;
}
