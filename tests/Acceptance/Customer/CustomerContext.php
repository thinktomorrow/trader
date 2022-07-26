<?php
declare(strict_types=1);

namespace Tests\Acceptance\Customer;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\Customer\CustomerApplication;
use Thinktomorrow\Trader\Application\Customer\RegisterCustomer;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCustomerRepository;

abstract class CustomerContext extends TestCase
{
    protected InMemoryCustomerRepository $customerRepository;
    protected CustomerApplication $customerApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerRepository = new InMemoryCustomerRepository();
        $this->customerRepository->autoGenerateNextReference();

        $this->customerReadRepository = new InMemoryCustomerReadRepository();

        $this->customerApplication = new CustomerApplication(
            $this->customerRepository,
            new EventDispatcherSpy(),
        );
    }

    protected function tearDown(): void
    {
        $this->customerRepository->clear();

        parent::tearDown();
    }

    protected function givenACustomerExists(string $email, bool $is_business = false, string $locale = 'nl_BE'): Customer
    {
        $customerId = $this->customerApplication->registerCustomer(new RegisterCustomer($email, $is_business, $locale, []));

        return $this->customerRepository->find($customerId);
    }
}
