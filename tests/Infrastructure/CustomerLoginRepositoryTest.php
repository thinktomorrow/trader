<?php
declare(strict_types=1);

namespace Tests\Infrastructure;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLogin;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCustomerRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCustomerLoginRepository;

final class CustomerLoginRepositoryTest extends TestCase
{
    use TestHelpers;

    /**
     * @test
     * @dataProvider entities
     */
    public function it_can_save_an_customer(CustomerLogin $customerLogin)
    {
        $this->createCustomer();

        foreach($this->repositories() as $customerRepository) {
            $customerRepository->save($customerLogin);
            $customerLogin->releaseEvents();

            $this->assertEquals($customerLogin, $customerRepository->find($customerLogin->customerId));
        }
    }

    /**
     * @test
     * @dataProvider entities
     */
    public function it_can_find_an_customer_login(CustomerLogin $customerLogin)
    {
        $this->createCustomer();

        foreach($this->repositories() as $customerRepository) {
            $customerRepository->save($customerLogin);
            $customerLogin->releaseEvents();

            $this->assertEquals($customerLogin, $customerRepository->find($customerLogin->customerId));
        }
    }

    private function repositories(): \Generator
    {
        yield new MysqlCustomerLoginRepository();
    }

    public function entities(): \Generator
    {
        yield [$this->createdCustomerLogin()];

        yield [CustomerLogin::create(
            CustomerId::fromString('abc'),
            Email::fromString('ben@thinktomorrow.be'),
            'xxx'
        )];
    }

    private function createCustomer()
    {
        (new MysqlCustomerRepository())->save($this->createdCustomer());
    }
}
