<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Customer;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Customer\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Customer\Address\ShippingAddress;
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

        $this->assertEquals([
            BillingAddress::class => null,
            ShippingAddress::class => null,
        ], $customer->getChildEntities());
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

    /** @test */
    public function it_can_update_shipping_address()
    {
        $customer = $this->createdCustomer();

        $addressPayload = [
            'address_id' => 'abc',
            'country_id' => 'NL',
            'line_1' => 'example 12',
            'line_2' => 'bus 2',
            'postal_code' => '1000',
            'city' => 'Amsterdam',
            'data' => "[]",
        ];

        $customer->updateShippingAddress($shippingAddress = ShippingAddress::fromMappedData($addressPayload, $customer->getMappedData()));
        $this->assertEquals($shippingAddress, $customer->getShippingAddress());

        $this->assertEquals(ShippingAddress::fromMappedData($addressPayload, $customer->getMappedData())->getMappedData(), $customer->getChildEntities()[ShippingAddress::class]);
    }

    /** @test */
    public function it_can_update_billing_address()
    {
        $customer = $this->createdCustomer();

        $addressPayload = [
            'address_id' => 'def',
            'country_id' => 'FR',
            'line_1' => 'rue de napoleon 222',
            'line_2' => 'bus 999',
            'postal_code' => '3000',
            'city' => 'Paris',
            'data' => "[]",
        ];

        $customer->updateBillingAddress($billingAddress = BillingAddress::fromMappedData($addressPayload, $customer->getMappedData()));
        $this->assertEquals($billingAddress, $customer->getBillingAddress());

        $this->assertEquals(BillingAddress::fromMappedData($addressPayload, $customer->getMappedData())->getMappedData(), $customer->getChildEntities()[BillingAddress::class]);
    }

    public function test_it_can_update_email()
    {
        $customer = $this->createdCustomer();

        $customer->updateEmail($email = Email::fromString('ben@tt.be'));
        $this->assertEquals($email, $customer->getEmail());
    }

    public function test_it_can_update_locale()
    {
        $customer = $this->createdCustomer();

        $customer->updateLocale($locale = Locale::fromString('nl_BE'));
        $this->assertEquals($locale, $customer->getLocale());
    }

    public function test_it_can_update_business_flag()
    {
        $customer = $this->createdCustomer();

        $customer->updateBusiness(true);
        $this->assertTrue($customer->isBusiness());

        $customer->updateBusiness(false);
        $this->assertFalse($customer->isBusiness());
    }

    private function createdCustomer(): Customer
    {
        return Customer::fromMappedData([
            'email' => 'ben@thinktomorrow.be',
            'is_business' => true,
            'customer_id' => 'yyy',
            'locale' => 'nl_BE',
            'data' => json_encode(['foo' => 'bar']),
        ], [
            BillingAddress::class => [
                'address_id' => 'abc',
                'country_id' => 'NL',
                'line_1' => 'example 12',
                'line_2' => 'bus 2',
                'postal_code' => '1000',
                'city' => 'Amsterdam',
                'data' => "[]",
            ],
            ShippingAddress::class => [
                'address_id' => 'def',
                'country_id' => 'FR',
                'line_1' => 'rue de napoleon 222',
                'line_2' => 'bus 999',
                'postal_code' => '3000',
                'city' => 'Paris',
                'data' => "[]",
            ],
        ]);
    }
}
