<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Domain\Model\Customer\Exceptions\CouldNotFindCustomer;

class MysqlCustomerRepository implements CustomerRepository
{
    private static $customerTable = 'trader_customers';

    public function save(Customer $customer): void
    {
        $state = $customer->getMappedData();

        if (!$this->exists($customer->customerId)) {
            DB::table(static::$customerTable)->insert($state);
        } else {
            DB::table(static::$customerTable)->where('customer_id', $customer->customerId)->update($state);
        }
    }

    private function exists(CustomerId $customerId): bool
    {
        return DB::table(static::$customerTable)->where('customer_id', $customerId->get())->exists();
    }

    public function find(CustomerId $customerId): Customer
    {
        $customerState = DB::table(static::$customerTable)
            ->where(static::$customerTable . '.customer_id', $customerId->get())
            ->first();

        if (!$customerState) {
            throw new CouldNotFindCustomer('No customer found by id [' . $customerId->get() . ']');
        }

        return Customer::fromMappedData((array) $customerState, []);
    }

    public function delete(CustomerId $customerId): void
    {
        DB::table(static::$customerTable)->where('customer_id', $customerId->get())->delete();
    }

    public function nextReference(): CustomerId
    {
        return CustomerId::fromString((string) Uuid::uuid4());
    }
}