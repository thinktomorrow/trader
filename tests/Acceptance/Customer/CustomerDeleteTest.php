<?php
declare(strict_types=1);

namespace Tests\Acceptance\Customer;

use Thinktomorrow\Trader\Application\Customer\DeleteCustomer;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Model\Customer\Exceptions\CouldNotFindCustomer;

class CustomerDeleteTest extends CustomerContext
{
    public function test_it_can_delete_a_customer()
    {
        $customer = $this->givenACustomerExists('ben@tt.be');

        $this->customerApplication->deleteCustomer(new DeleteCustomer(
            $customer->customerId->get(),
        ));

        $this->assertFalse($this->customerRepository->existsByEmail(Email::fromString('ben@tt.be')));

        $this->expectException(CouldNotFindCustomer::class);
        $this->customerRepository->find($customer->customerId);
    }
}
