<?php
declare(strict_types=1);

namespace Tests\Acceptance\Customer;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\Customer\CustomerApplication;
use Thinktomorrow\Trader\Application\Customer\Read\CustomerReadRepository;
use Thinktomorrow\Trader\Application\Customer\RegisterCustomer;
use Thinktomorrow\Trader\Application\Customer\UpdateBillingAddress;
use Thinktomorrow\Trader\Application\Customer\UpdateShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerRepository;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCustomerRepository;

abstract class CustomerContext extends TestCase
{
    protected CustomerRepository $customerRepository;
    protected CustomerApplication $customerApplication;
    protected CustomerReadRepository $customerReadRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerRepository = $this->customerReadRepository = new InMemoryCustomerRepository();
        $this->customerRepository->autoGenerateNextReference();

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

    protected function andCustomerHasBillingAddress(string $customerId, ?string $countryId = null, ?string $line1 = null, ?string $line2 = null, ?string $postalCode = null, ?string $city = null): void
    {
        $this->customerApplication->updateBillingAddress(new UpdateBillingAddress($customerId, $countryId, $line1, $line2, $postalCode, $city));
    }

    protected function andCustomerHasShippingAddress(string $customerId, ?string $countryId = null, ?string $line1 = null, ?string $line2 = null, ?string $postalCode = null, ?string $city = null): void
    {
        $this->customerApplication->updateShippingAddress(new UpdateShippingAddress($customerId, $countryId, $line1, $line2, $postalCode, $city));
    }
}
