<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLogin;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCustomerLoginRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCustomerRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

final class CustomerLoginRepositoryTest extends TestCase
{
    use RefreshDatabase;
    use PrepareWorld;

    /**
     * @test
     * @dataProvider entities
     */
    public function it_can_save_an_customer(CustomerLogin $customerLogin)
    {
        (new MysqlCustomerRepository(new TestContainer()))->save($this->createCustomer());

        foreach ($this->repositories() as $i => $customerRepository) {
            $this->prepareCustomer($i);
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
        (new MysqlCustomerRepository(new TestContainer()))->save($this->createCustomer());

        foreach ($this->repositories() as $i => $customerRepository) {
            $this->prepareCustomer($i);
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
        yield [$this->createCustomerLogin()];

        yield [CustomerLogin::create(
            CustomerId::fromString('ccc-123'),
            Email::fromString('ben@thinktomorrow.be'),
            'xxx'
        )];
    }
}
