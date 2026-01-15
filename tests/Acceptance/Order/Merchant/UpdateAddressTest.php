<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order\Merchant;

use Tests\Acceptance\Cart\CartContext;
use Thinktomorrow\Trader\Application\Order\Merchant\UpdateBillingAddress;
use Thinktomorrow\Trader\Application\Order\Merchant\UpdateShippingAddress;
use Thinktomorrow\Trader\Domain\Common\Address\Address;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Order\Events\Merchant\BillingAddressUpdatedByMerchant;
use Thinktomorrow\Trader\Domain\Model\Order\Events\Merchant\ShippingAddressUpdatedByMerchant;

class UpdateAddressTest extends CartContext
{
    public function test_merchant_can_change_shipping_address()
    {
        $order = $this->orderContext->createDefaultOrder();

        $this->orderContext->apps()->merchantOrderApplication()->updateShippingAddress(new UpdateShippingAddress(
            $order->orderId->get(),
            'NL',
            'line-1 updated',
            'line-2',
            'postal-code',
            'city',
        ), []);

        $order = $this->orderContext->findOrder($order->orderId);

        $this->assertEquals(new Address(CountryId::fromString('NL'), 'line-1 updated', 'line-2', 'postal-code', 'city', ), $order->getShippingAddress()->getAddress());

        $lastEvent = last($this->orderContext->apps()->getEventDispatcher()->releaseDispatchedEvents());

        $this->assertEquals(new ShippingAddressUpdatedByMerchant($order->orderId, [
            'country_id' => ['old' => 'BE', 'new' => 'NL'],
            'line1' => ['old' => 'Lierseweg 81', 'new' => 'line-1 updated'],
            'line2' => ['old' => null, 'new' => 'line-2'],
            'postal_code' => ['old' => '2200', 'new' => 'postal-code'],
            'city' => ['old' => 'Herentals', 'new' => 'city'],
        ], []), $lastEvent);
    }

    public function test_merchant_can_change_billing_address()
    {
        $order = $this->orderContext->createDefaultOrder();

        $this->orderContext->apps()->merchantOrderApplication()->updateBillingAddress(new UpdateBillingAddress(
            $order->orderId->get(),
            'NL',
            'line-1 updated',
            'line-2',
            'postal-code',
            'city',
        ), []);

        $order = $this->orderContext->findOrder($order->orderId);

        $this->assertEquals(new Address(CountryId::fromString('NL'), 'line-1 updated', 'line-2', 'postal-code', 'city', ), $order->getBillingAddress()->getAddress());

        $lastEvent = last($this->orderContext->apps()->getEventDispatcher()->releaseDispatchedEvents());

        $this->assertEquals(new BillingAddressUpdatedByMerchant($order->orderId, [
            'line1' => ['old' => 'Example 12', 'new' => 'line-1 updated'],
            'line2' => ['old' => null, 'new' => 'line-2'],
            'postal_code' => ['old' => '1000', 'new' => 'postal-code'],
            'city' => ['old' => 'Amsterdam', 'new' => 'city'],
        ], []), $lastEvent);
    }

    public function test_if_shipping_address_is_not_changed_no_event_is_triggered()
    {
        $order = $this->orderContext->createDefaultOrder();

        $address = $order->getShippingAddress()->getMappedData();

        $values = [
            $order->orderId->get(),
            $address['country_id'],
            $address['line_1'],
            $address['line_2'],
            $address['postal_code'],
            $address['city'],
        ];

        $this->orderContext->apps()->merchantOrderApplication()->updateShippingAddress(new UpdateShippingAddress(...$values), []);

        $events = $this->orderContext->apps()->getEventDispatcher()->releaseDispatchedEvents();

        $this->assertCount(0, $this->orderContext->apps()->getEventDispatcher()->releaseDispatchedEvents());
    }
}
