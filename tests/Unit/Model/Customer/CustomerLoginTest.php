<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Customer;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLogin;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\Events\PasswordChanged;

class CustomerLoginTest extends TestCase
{
    public function test_it_can_create_a_customer_login()
    {
        $customerLogin = CustomerLogin::create(
            CustomerId::fromString('abc'),
            $customerEmail = Email::fromString('ben@thinktomorrow.be'),
            'xxx',
        );

        $this->assertEquals([
            'email' => $customerEmail->get(),
            'password' => 'xxx',
        ], $customerLogin->getMappedData());

        $this->assertEquals($customerEmail, $customerLogin->getEmail());
        $this->assertEquals('xxx', $customerLogin->getPassword());
        $this->assertEquals([], $customerLogin->getChildEntities());
    }

    public function test_it_can_be_build_from_raw_data()
    {
        $customer = $this->createdCustomerLogin();

        $this->assertEquals([
            'email' => 'ben@thinktomorrow.be',
            'password' => 'xxx',
        ], $customer->getMappedData());
    }

    public function test_it_can_change_password()
    {
        $customer = $this->createdCustomerLogin();
        $customer->changePassword('yyy');

        $this->assertEquals('yyy', $customer->getPassword());

        $this->assertEquals([
            new PasswordChanged($customer->customerId),
        ], $customer->releaseEvents());
    }

    private function createdCustomerLogin(): CustomerLogin
    {
        return CustomerLogin::fromMappedData([
            'customer_id' => 'abc',
            'email' => 'ben@thinktomorrow.be',
            'password' => 'xxx',
        ]);
    }
}
