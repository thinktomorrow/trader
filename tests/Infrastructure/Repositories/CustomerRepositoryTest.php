<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerRead;
use Thinktomorrow\Trader\Domain\Common\Address\Address;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Customer\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Customer\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Customer\Exceptions\CouldNotFindCustomer;
use Thinktomorrow\Trader\Testing\Order\OrderContext;

final class CustomerRepositoryTest extends TestCase
{
    #[DataProvider('customers')]
    public function test_it_can_save_and_find_a_customer(Customer $customer)
    {
        foreach (OrderContext::drivers() as $orderContext) {

            $orderContext->createCountry('BE');

            $repository = $orderContext->repos()->customerRepository();

            $repository->save($customer);

            $customer->releaseEvents();

            $this->assertEquals($customer, $repository->find($customer->customerId));
        }
    }

    #[DataProvider('customers')]
    public function test_it_can_save_and_find_a_customer_by_email(Customer $customer)
    {
        foreach (OrderContext::drivers() as $orderContext) {

            $orderContext->createCountry('BE');

            $repository = $orderContext->repos()->customerRepository();

            $repository->save($customer);

            $customer->releaseEvents();

            $this->assertEquals($customer, $repository->findByEmail($customer->getEmail()));
        }
    }

    #[DataProvider('customers')]
    public function test_it_can_delete_a_customer(Customer $customer)
    {
        $customersNotFound = 0;

        foreach (OrderContext::drivers() as $orderContext) {

            $orderContext->createCountry('BE');

            $repository = $orderContext->repos()->customerRepository();

            $repository->save($customer);

            $repository->delete($customer->customerId);

            try {
                $repository->find($customer->customerId);
            } catch (CouldNotFindCustomer $e) {
                $customersNotFound++;
            }
        }

        $this->assertEquals(count(OrderContext::drivers()), $customersNotFound);
    }

    public function test_it_can_generate_a_next_reference()
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->customerRepository();

            $this->assertInstanceOf(CustomerId::class, $repository->nextReference());
        }
    }

    #[DataProvider('customers')]
    public function test_it_can_get_customer_read(Customer $customer)
    {
        foreach (OrderContext::drivers() as $orderContext) {

            $orderContext->createCountry('BE');

            $repository = $orderContext->repos()->customerRepository();

            $repository->save($customer);

            $customer->releaseEvents();

            $this->assertInstanceOf(CustomerRead::class, $repository->findCustomer($customer->customerId));
        }
    }

    public static function customers(): \Generator
    {
        yield [Customer::create(
            CustomerId::fromString('xxx-1'),
            Email::fromString('ben+1@thinktomorrow.be'),
            false,
            Locale::fromString('nl-be')
        )];

        yield [Customer::create(
            CustomerId::fromString('xxx-2'),
            Email::fromString('ben+2@thinktomorrow.be'),
            true,
            Locale::fromString('nl_BE')
        )];

        $customerWithAddress = Customer::create(
            CustomerId::fromString('xxx-3'),
            Email::fromString('ben+3@thinktomorrow.be'),
            true,
            Locale::fromString('nl_BE')
        );

        $customerWithAddress->updateBillingAddress(
            BillingAddress::create(CustomerId::fromString('xxx-3'), new Address(CountryId::fromString('BE'), 'street 123', 'bus 456', '2200', 'Herentals'), [])
        );

        $customerWithAddress->updateShippingAddress(
            ShippingAddress::create(CustomerId::fromString('xxx-3'), new Address(CountryId::fromString('BE'), 'street 123', 'bus 456', '2200', 'Herentals'), [])
        );

        yield [$customerWithAddress];
    }
}
