<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Common\Address\AddressType;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Domain\Model\Customer\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Customer\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Customer\Exceptions\CouldNotFindCustomer;

class MysqlCustomerRepository implements CustomerRepository
{
    private static $customerTable = 'trader_customers';
    private static $customerAddressTable = 'trader_customer_addresses';

    public function save(Customer $customer): void
    {
        $state = $customer->getMappedData();

        if (! $this->exists($customer->customerId)) {
            DB::table(static::$customerTable)->insert($state);
        } else {
            DB::table(static::$customerTable)->where('customer_id', $customer->customerId->get())->update($state);
        }

        $this->upsertAddresses($customer);
    }

    private function upsertAddresses(Customer $customer): void
    {
        if ($shippingAddressState = $customer->getChildEntities()[ShippingAddress::class]) {
            DB::table(static::$customerAddressTable)
                ->updateOrInsert([
                    'customer_id' => $customer->customerId->get(),
                    'type' => AddressType::shipping->value,
                ], $shippingAddressState);
        } else {
            DB::table(static::$customerAddressTable)
                ->where('customer_id', $customer->customerId->get())
                ->where('type', AddressType::shipping->value)
                ->delete();
        }

        if ($billingAddressState = $customer->getChildEntities()[BillingAddress::class]) {
            DB::table(static::$customerAddressTable)
                ->updateOrInsert([
                    'customer_id' => $customer->customerId->get(),
                    'type' => AddressType::billing->value,
                ], $billingAddressState);
        } else {
            DB::table(static::$customerAddressTable)
                ->where('customer_id', $customer->customerId->get())
                ->where('type', AddressType::billing->value)
                ->delete();
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

        if (! $customerState) {
            throw new CouldNotFindCustomer('No customer found by id [' . $customerId->get() . ']');
        }

        $addressStates = DB::table(static::$customerAddressTable)
            ->where(static::$customerAddressTable . '.customer_id', $customerId->get())
            ->get();

        $shippingAddressState = $addressStates->first(fn ($address) => $address->type == AddressType::shipping->value);
        $billingAddressState = $addressStates->first(fn ($address) => $address->type == AddressType::billing->value);

        return Customer::fromMappedData((array) $customerState, [
            ShippingAddress::class => $shippingAddressState ? (array)$shippingAddressState : null,
            BillingAddress::class => $billingAddressState ? (array)$billingAddressState : null,
        ]);
    }

    public function findByEmail(Email $email): Customer
    {
        $customerState = DB::table(static::$customerTable)
            ->where(static::$customerTable . '.email', $email->get())
            ->first();

        if (! $customerState) {
            throw new CouldNotFindCustomer('No customer found by email [' . $email->get() . ']');
        }

        return Customer::fromMappedData((array) $customerState, []);
    }

    public function existsByEmail(Email $email, ?CustomerId $ignoredCustomerId = null): bool
    {
        $builder = DB::table(static::$customerTable)->where('email', $email->get());

        if($ignoredCustomerId) {
            $builder->where('customer_id', '<>', $ignoredCustomerId->get());
        }

        return $builder->exists();
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
