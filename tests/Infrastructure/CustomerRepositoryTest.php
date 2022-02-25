<?php
declare(strict_types=1);

namespace Tests\Infrastructure;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Infrastructure\Test\InMemoryCustomerRepository;
use Thinktomorrow\Trader\Domain\Model\Customer\Exceptions\CouldNotFindCustomer;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCustomerRepository;

final class CustomerRepositoryTest extends TestCase
{
    use TestHelpers;

    /**
     * @test
     * @dataProvider customers
     */
    public function it_can_save_an_customer(Customer $customer)
    {
        foreach($this->customerRepositories() as $customerRepository) {
            $customerRepository->save($customer);
            $customer->releaseEvents();

            $this->assertEquals($customer, $customerRepository->find($customer->customerId));
        }
    }

    /**
     * @test
     * @dataProvider customers
     */
    public function it_can_find_an_customer(Customer $customer)
    {
        foreach($this->customerRepositories() as $customerRepository) {
            $customerRepository->save($customer);
            $customer->releaseEvents();

            $this->assertEquals($customer, $customerRepository->find($customer->customerId));
        }
    }

    /**
     * @test
     * @dataProvider customers
     */
    public function it_can_delete_an_customer(Customer $customer)
    {
        $customersNotFound = 0;

        foreach($this->customerRepositories() as $customerRepository) {
            $customerRepository->save($customer);
            $customerRepository->delete($customer->customerId);

            try{
                $customerRepository->find($customer->customerId);
            } catch (CouldNotFindCustomer $e) {
                $customersNotFound++;
            }
        }

        $this->assertEquals(count(iterator_to_array($this->customerRepositories())), $customersNotFound);
    }

    /** @test */
    public function it_can_generate_a_next_reference()
    {
        foreach($this->customerRepositories() as $customerRepository) {
            $this->assertInstanceOf(CustomerId::class, $customerRepository->nextReference());
        }
    }

    private function customerRepositories(): \Generator
    {
        yield new InMemoryCustomerRepository();
        yield new MysqlCustomerRepository();
    }

    public function customers(): \Generator
    {
        yield [$this->createdCustomer()];

        yield [Customer::create(
            CustomerId::fromString('xxx'),
            Email::fromString('ben@thinktomorrow.be'),
            'Ben', 'Cavens'
        )];
    }
}
