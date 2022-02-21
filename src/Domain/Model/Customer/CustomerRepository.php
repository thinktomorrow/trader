<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Customer;

interface CustomerRepository
{
    public function save(Customer $customer): void;

    public function find(CustomerId $customerId): Customer;

    public function delete(CustomerId $customerId): void;

    public function nextReference(): CustomerId;
}
