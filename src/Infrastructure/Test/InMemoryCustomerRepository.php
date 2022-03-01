<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test;

use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Domain\Model\Customer\Exceptions\CouldNotFindCustomer;

final class InMemoryCustomerRepository implements CustomerRepository
{
    private static array $customers = [];

    private string $nextReference = 'ccc-123';

    public function save(Customer $customer): void
    {
        static::$customers[$customer->customerId->get()] = $customer;
    }

    public function find(CustomerId $customerId): Customer
    {
        if(!isset(static::$customers[$customerId->get()])) {
            throw new CouldNotFindCustomer('No customer found by id ' . $customerId);
        }

        return static::$customers[$customerId->get()];
    }

    public function delete(CustomerId $customerId): void
    {
        if(!isset(static::$customers[$customerId->get()])) {
            throw new CouldNotFindCustomer('No customer found by id ' . $customerId);
        }

        unset(static::$customers[$customerId->get()]);
    }

    public function nextReference(): CustomerId
    {
        return CustomerId::fromString($this->nextReference);
    }

    // For testing purposes only
    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public function clear()
    {
        static::$customers = [];
    }
}
