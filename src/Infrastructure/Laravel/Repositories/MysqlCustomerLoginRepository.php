<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Customer\Exceptions\CouldNotFindCustomer;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLogin;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLoginRepository;

class MysqlCustomerLoginRepository implements CustomerLoginRepository
{
    private static $customerTable = 'trader_customers';

    public function save(CustomerLogin $customerLogin): void
    {
        DB::table(static::$customerTable)->where('customer_id', $customerLogin->customerId)->update(
            $customerLogin->getMappedData()
        );
    }

    public function find(CustomerId $customerId): CustomerLogin
    {
        $customerState = DB::table(static::$customerTable)
            ->where(static::$customerTable . '.customer_id', $customerId->get())
            ->first();

        if (! $customerState) {
            throw new CouldNotFindCustomer('No customer found by id [' . $customerId->get() . ']');
        }

        return CustomerLogin::fromMappedData((array) $customerState, []);
    }
}
