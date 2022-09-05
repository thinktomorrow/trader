<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Address\Address;
use Thinktomorrow\Trader\Domain\Common\Address\AddressType;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

class OrderAddressTest extends TestCase
{
    public function test_it_can_create_billing_address()
    {
        $address = BillingAddress::create($orderId = OrderId::fromString('abc'), new Address(
            CountryId::fromString('NL'),
            'line-1',
            'line-2',
            'postal-code',
            'city',
        ), ['foo' => 'bar']);

        $this->assertEquals($orderId, $address->orderId);
        $this->assertEquals([
            'type' => AddressType::billing->value,
            'line_1' => 'line-1',
            'line_2' => 'line-2',
            'postal_code' => 'postal-code',
            'city' => 'city',
            'country_id' => 'NL',
            'order_id' => 'abc',
            'data' => json_encode(['foo' => 'bar']),
        ], $address->getMappedData());
    }

    public function test_it_can_create_shipping_address()
    {
        $address = ShippingAddress::create($orderId = OrderId::fromString('abc'), new Address(
            CountryId::fromString('NL'),
            'line-1',
            'line-2',
            'postal-code',
            'city',
        ), ['foo' => 'bar']);

        $this->assertEquals($orderId, $address->orderId);
        $this->assertEquals([
            'type' => AddressType::shipping->value,
            'line_1' => 'line-1',
            'line_2' => 'line-2',
            'postal_code' => 'postal-code',
            'city' => 'city',
            'country_id' => 'NL',
            'order_id' => 'abc',
            'data' => json_encode(['foo' => 'bar']),
        ], $address->getMappedData());
    }
}
