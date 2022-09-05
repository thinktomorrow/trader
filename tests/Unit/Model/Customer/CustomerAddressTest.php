<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Customer;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Address\Address;
use Thinktomorrow\Trader\Domain\Common\Address\AddressType;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Customer\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Customer\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;

class CustomerAddressTest extends TestCase
{
    public function test_it_can_create_billing_address()
    {
        $address = BillingAddress::create($customerId = CustomerId::fromString('abc'), new Address(
            CountryId::fromString('NL'),
            'line-1',
            'line-2',
            'postal-code',
            'city',
        ), ['foo' => 'bar']);

        $this->assertEquals($customerId, $address->customerId);
        $this->assertEquals([
            'type' => AddressType::billing->value,
            'line_1' => 'line-1',
            'line_2' => 'line-2',
            'postal_code' => 'postal-code',
            'city' => 'city',
            'country_id' => 'NL',
            'customer_id' => 'abc',
            'data' => json_encode(['foo' => 'bar']),
        ], $address->getMappedData());
    }

    public function test_it_can_create_shipping_address()
    {
        $address = ShippingAddress::create($customerId = CustomerId::fromString('abc'), new Address(
            CountryId::fromString('NL'),
            'line-1',
            'line-2',
            'postal-code',
            'city',
        ), ['foo' => 'bar']);

        $this->assertEquals($customerId, $address->customerId);
        $this->assertEquals([
            'type' => AddressType::shipping->value,
            'line_1' => 'line-1',
            'line_2' => 'line-2',
            'postal_code' => 'postal-code',
            'city' => 'city',
            'country_id' => 'NL',
            'customer_id' => 'abc',
            'data' => json_encode(['foo' => 'bar']),
        ], $address->getMappedData());
    }

    public function test_it_can_replace_address()
    {
        $address = ShippingAddress::create($customerId = CustomerId::fromString('abc'), new Address(
            CountryId::fromString('NL'),
            'line-1',
            'line-2',
            'postal-code',
            'city',
        ), ['foo' => 'bar']);

        $address->replaceAddress($replacedAddress = new Address(
            CountryId::fromString('BE'),
            'line-1 edited',
            'line-2 edited',
            'postal-code edited',
            'city edited',
        ));

        $this->assertEquals($replacedAddress, $address->getAddress());
    }
}
