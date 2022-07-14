<?php
declare(strict_types=1);

namespace Tests\Acceptance\Customer;

use Thinktomorrow\Trader\Application\Customer\UpdateEmail;
use Thinktomorrow\Trader\Domain\Common\Email;

class CustomerUpdateEmailTest extends CustomerContext
{
    public function test_it_can_update_email()
    {
        $customer = $this->givenACustomerExists('ben@tt.be');

        $this->customerApplication->updateEmail(new UpdateEmail(
            $customer->customerId->get(),
            'ben@tt.be',
            'benjamin@tt.be'
        ));

        $customer = $this->customerRepository->find($customer->customerId);

        $this->assertEquals(Email::fromString('benjamin@tt.be'), $customer->getEmail());
    }

    public function test_it_cannot_update_email_if_old_email_does_not_belong_to_customer()
    {
        $this->givenACustomerExists('otherben@tt.be');
        $customer = $this->givenACustomerExists('ben@tt.be');

        $this->expectException(\InvalidArgumentException::class);
        $this->customerApplication->updateEmail(new UpdateEmail(
            $customer->customerId->get(),
            'otherben@tt.be',
            'benjamin@tt.be'
        ));
    }

    public function test_it_cannot_update_email_if_new_email_already_exists()
    {
        $this->givenACustomerExists('otherben@tt.be');
        $customer = $this->givenACustomerExists('ben@tt.be');

        $this->expectException(\InvalidArgumentException::class);
        $this->customerApplication->updateEmail(new UpdateEmail(
            $customer->customerId->get(),
            'ben@tt.be',
            'otherben@tt.be'
        ));
    }
}
