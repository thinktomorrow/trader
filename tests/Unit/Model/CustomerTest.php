<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Locale;
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
            false,
            Locale::fromString('nl_BE'),
        );

        $this->assertEquals([
            'customer_id' => $customerId->get(),
            'email' => $customerEmail->get(),
            'is_business' => false,
            'locale' => 'nl_BE',
            'data' => json_encode([]),
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
            'customer_id' => 'yyy',
            'is_business' => true,
            'locale' => 'nl_BE',
            'data' => json_encode(['foo' => 'bar']),
        ], $customer->getMappedData());
    }

    private function createdCustomer(): Customer
    {
        return Customer::fromMappedData([
            'email' => 'ben@thinktomorrow.be',
            'is_business' => true,
            'customer_id' => 'yyy',
            'locale' => 'nl_BE',
            'data' => json_encode(['foo' => 'bar']),
        ]);
    }
}
