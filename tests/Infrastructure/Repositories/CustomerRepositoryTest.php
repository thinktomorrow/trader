<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Common\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Customer\Exceptions\CouldNotFindCustomer;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCustomerRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCustomerRepository;

final class CustomerRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider customers
     */
    public function it_can_save_and_find_a_customer(Customer $customer)
    {
        foreach($this->repositories() as $repository) {
            $repository->save($customer);
            $customer->releaseEvents();

            $this->assertEquals($customer, $repository->find($customer->customerId));
        }
    }

    /**
     * @test
     * @dataProvider customers
     */
    public function it_can_delete_a_customer(Customer $customer)
    {
        $customersNotFound = 0;

        foreach($this->repositories() as $repository) {
            $repository->save($customer);
            $repository->delete($customer->customerId);

            try{
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
        foreach($this->repositories() as $repository) {
            $this->assertInstanceOf(CustomerId::class, $repository->nextReference());
        }
    }

    private function repositories(): \Generator
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
