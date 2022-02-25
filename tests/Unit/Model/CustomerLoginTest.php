<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\CustomerLogin;

class CustomerLoginTest extends TestCase
{
    /** @test */
    public function it_can_create_a_customer_login()
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

        $this->assertEquals([], $customerLogin->getChildEntities());
    }

    /** @test */
    public function it_can_be_build_from_raw_data()
    {
        $customer = $this->createdCustomerLogin();

        $this->assertEquals([
            'email' => 'ben@thinktomorrow.be',
            'password' => 'xxx',
        ], $customer->getMappedData());
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
