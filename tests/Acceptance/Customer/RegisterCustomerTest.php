<?php
declare(strict_types=1);

namespace Tests\Acceptance\Customer;

use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Application\Customer\RegisterCustomer;

class RegisterCustomerTest extends CustomerContext
{
    public function test_it_can_register_a_customer()
    {
        $customerId = $this->customerApplication->registerCustomer(new RegisterCustomer(
            'ben@tt.be',
            false,
            'nl_BE',
            $data = [
                'firstname' => 'ben',
                'lastname' => 'cavens',
            ]
        ));

        $customer = $this->customerRepository->find($customerId);

        $this->assertEquals(Email::fromString('ben@tt.be'), $customer->getEmail());
        $this->assertEquals(Locale::fromString('nl_BE'), $customer->getLocale());
        $this->assertFalse($customer->isBusiness());
        $this->assertEquals($data, $customer->getData());
    }

    public function test_it_cannot_register_with_existing_email()
    {
        $this->givenACustomerExists('ben@tt.be');

        $this->expectException(\InvalidArgumentException::class);
        $this->customerApplication->registerCustomer(new RegisterCustomer('ben@tt.be', false, 'nl_BE', []));
    }
}
