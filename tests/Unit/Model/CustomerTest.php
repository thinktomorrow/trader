<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;

class CustomerTest extends TestCase
{
    /** @test */
    public function it_can_create_a_customer()
    {
        $customer = Customer::create(
            $customerId = CustomerId::fromString('yyy'),
            $customerEmail = Email::fromString('ben@thinktomorrow.be'),
            'bennie',
            'caveman'
        );

        $this->assertEquals([
            'customer_id' => $customerId->get(),
            'email' => $customerEmail->get(),
            'firstname' => 'bennie',
            'lastname' => 'caveman',
        ], $customer->getMappedData());

        $this->assertEquals([], $customer->getChildEntities());
    }

    /** @test */
    public function it_can_be_build_from_raw_data()
    {
        $customer = $this->createdCustomer();

        $this->assertEquals(CustomerId::fromString('yyy'), $customer->customerId);
        $this->assertEquals([
            'email' => 'ben@thinktomorrow.be',
            'firstname' => 'Ben',
            'lastname' => 'Cavens',
            'customer_id' => 'yyy',
        ], $customer->getMappedData());
    }

    private function createdCustomer(): Customer
    {
        return Customer::fromMappedData([
            'email' => 'ben@thinktomorrow.be',
            'firstname' => 'Ben',
            'lastname' => 'Cavens',
            'customer_id' => 'yyy',
        ]);
    }
}