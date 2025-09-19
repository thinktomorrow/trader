<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Application\Customer\Read\CustomerBillingAddress;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerReadRepository;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerShippingAddress;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Domain\Model\Customer\Exceptions\CouldNotFindCustomer;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\CustomerRead\DefaultCustomerBillingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\CustomerRead\DefaultCustomerRead;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\CustomerRead\DefaultCustomerShippingAddress;

final class InMemoryCustomerRepository implements CustomerRepository, CustomerReadRepository, InMemoryRepository
{
    /** @var Customer[] */
    public static array $customers = [];

    private bool $autoGenerateNextReference = false;
    private string $nextReference = 'ccc-123';

    public function save(Customer $customer): void
    {
        static::$customers[$customer->customerId->get()] = $customer;
    }

    public function find(CustomerId $customerId): Customer
    {
        if (! isset(static::$customers[$customerId->get()])) {
            throw new CouldNotFindCustomer('No customer found by id ' . $customerId);
        }

        return static::$customers[$customerId->get()];
    }

    public function findByEmail(Email $email): Customer
    {
        foreach (static::$customers as $customer) {
            if ($customer->getEmail()->equals($email)) {
                return $customer;
            }
        }

        throw new CouldNotFindCustomer('No customer found by email ' . $email->get());
    }

    public function existsByEmail(Email $email, ?CustomerId $ignoredCustomerId = null): bool
    {
        foreach (static::$customers as $customer) {
            if ($customer->getEmail()->equals($email)) {
                if ($ignoredCustomerId && $customer->customerId->equals($ignoredCustomerId)) {
                    trap('sisi');

                    continue;
                }

                return true;
            }
        }

        return false;
    }

    public function delete(CustomerId $customerId): void
    {
        if (! isset(static::$customers[$customerId->get()])) {
            throw new CouldNotFindCustomer('No customer found by id ' . $customerId);
        }

        unset(static::$customers[$customerId->get()]);
    }

    public function nextReference(): CustomerId
    {
        if ($this->autoGenerateNextReference) {
            return CustomerId::fromString('customer-id-' . mt_rand(111, 999));
        }

        return CustomerId::fromString($this->nextReference);
    }

    // For testing purposes only
    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public function autoGenerateNextReference(): void
    {
        $this->autoGenerateNextReference = true;
    }

    public static function clear()
    {
        static::$customers = [];
    }

    public function findCustomer(CustomerId $customerId): \Thinktomorrow\Trader\Application\Customer\Read\CustomerRead
    {
        $customer = $this->find($customerId);

        $shippingAddress = $customer->getShippingAddress() ? DefaultCustomerShippingAddress::fromMappedData(
            $customer->getShippingAddress()->getMappedData(),
            $customer->getMappedData()
        ) : null;

        $billingAddress = $customer->getBillingAddress() ? DefaultCustomerBillingAddress::fromMappedData(
            $customer->getBillingAddress()->getMappedData(),
            $customer->getMappedData()
        ) : null;

        return DefaultCustomerRead::fromMappedData($customer->getMappedData(), [
            CustomerBillingAddress::class => $billingAddress,
            CustomerShippingAddress::class => $shippingAddress,
        ]);
    }
}
