<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCustomerRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCustomerRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

final class CustomerRepositoryTest extends TestCase
{
    use RefreshDatabase;
    use PrepareWorld;

    /**
     * @test
     * @dataProvider customers
     */
    public function it_can_save_and_find_a_customer(Customer $customer)
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->prepareCountries($i);
            $repository->save($customer);
            $customer->releaseEvents();

            $this->assertEquals($customer, $repository->find($customer->customerId));
        }
    }

    /**
     * @test
     * @dataProvider customers
     */
    public function it_can_save_and_find_a_customer_by_email(Customer $customer)
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->prepareCountries($i);
            $repository->save($customer);
            $customer->releaseEvents();

            $this->assertEquals($customer, $repository->findByEmail($customer->getEmail()));
        }
    }

    /**
     * @test
     * @dataProvider customers
     */
    public function it_can_delete_a_customer(Customer $customer)
    {
        $customersNotFound = 0;

        foreach ($this->repositories() as $i => $repository) {
            $this->prepareCountries($i);
            $repository->save($customer);
            $repository->delete($customer->customerId);

            try {
                $repository->find($customer->customerId);
            } catch (CouldNotFindCustomer $e) {
                $customersNotFound++;
            }
        }

        $this->assertEquals(count(iterator_to_array($this->repositories())), $customersNotFound);
    }

    /** @test */
    public function it_can_generate_a_next_reference()
    {
        foreach ($this->repositories() as $repository) {
            $this->assertInstanceOf(CustomerId::class, $repository->nextReference());
        }
    }

    /**
     * @dataProvider customers
     */
    public function test_it_can_get_customer_read(Customer $customer)
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->prepareCountries($i);
            $repository->save($customer);
            $customer->releaseEvents();

            $this->assertInstanceOf(CustomerRead::class, $repository->findCustomer($customer->customerId));
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryCustomerRepository();
        yield new MysqlCustomerRepository(new TestContainer());
    }

    public function customers(): \Generator
    {
        yield [$this->createCustomer()];

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
