<?php

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Customer\Exceptions\CouldNotFindCustomer;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLogin;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLoginRepository;

class InMemoryCustomerLoginRepository implements CustomerLoginRepository
{
    /** @var array<string,CustomerLogin> */
    private static array $items = [];

    public function save(CustomerLogin $customerLogin): void
    {
        self::$items[$customerLogin->customerId->get()] = $customerLogin;
    }

    public function find(CustomerId $customerId): CustomerLogin
    {
        if (! isset(self::$items[$customerId->get()])) {
            throw new CouldNotFindCustomer('No customer found by id [' . $customerId->get() . ']');
        }

        return self::$items[$customerId->get()];
    }

    /**
     * Test helper
     */
    public function all(): array
    {
        return array_values(self::$items);
    }

    public static function clear(): void
    {
        self::$items = [];
    }
}
