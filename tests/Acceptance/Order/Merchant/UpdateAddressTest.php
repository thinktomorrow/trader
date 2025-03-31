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
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;

class UpdateAddressTest extends CartContext
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = new InMemoryOrderRepository();
    }

    public function test_merchant_can_change_shipping_address()
    {
        $order = $this->createOrder(
            ['order_id' => 'xxx'],
            [],
            [],
            [],
            [],
            $this->createOrderShippingAddress([
                'country_id' => 'BE',
                'line_1' => 'line-1',
                'line_2' => 'line-2',
                'postal_code' => 'postal-code',
                'city' => 'city',
            ]),
        );

        $this->orderRepository->save($order);

        $this->merchantOrderApplication->updateShippingAddress(new UpdateShippingAddress(
            $order->orderId->get(),
            'NL',
            'line-1 updated',
            'line-2',
            'postal-code',
            'city',
        ), []);

        $order = $this->orderRepository->find($order->orderId);

        $this->assertEquals(new Address(CountryId::fromString('NL'), 'line-1 updated', 'line-2', 'postal-code', 'city',), $order->getShippingAddress()->getAddress());

        $this->assertEquals(new ShippingAddressUpdatedByMerchant($order->orderId, [
            'country_id' => ['old' => 'BE', 'new' => 'NL'], 'line1' => ['old' => 'line-1', 'new' => 'line-1 updated'],
        ], []), $this->eventDispatcher->releaseDispatchedEvents()[2]);
    }

    public function test_merchant_can_change_billing_address()
    {
        $order = $this->createOrder(
            ['order_id' => 'xxx'],
            [],
            [],
            [],
            [],
            null,
            $this->createOrderBillingAddress([
                'country_id' => 'BE',
                'line_1' => 'line-1',
                'line_2' => 'line-2',
                'postal_code' => 'postal-code',
                'city' => 'city',
            ])
        );

        $this->orderRepository->save($order);

        $this->merchantOrderApplication->updateBillingAddress(new UpdateBillingAddress(
            $order->orderId->get(),
            'NL',
            'line-1 updated',
            'line-2',
            'postal-code',
            'city',
        ), []);

        $order = $this->orderRepository->find($order->orderId);

        $this->assertEquals(new Address(CountryId::fromString('NL'), 'line-1 updated', 'line-2', 'postal-code', 'city',), $order->getBillingAddress()->getAddress());

        $this->assertEquals(new BillingAddressUpdatedByMerchant($order->orderId, [
            'country_id' => ['old' => 'BE', 'new' => 'NL'], 'line1' => ['old' => 'line-1', 'new' => 'line-1 updated'],
        ], []), $this->eventDispatcher->releaseDispatchedEvents()[2]);
    }

    public function test_if_shipping_address_is_not_changed_no_event_is_triggered()
    {
        $order = $this->createOrder(
            ['order_id' => 'xxx'],
            [],
            [],
            [],
            [],
            $this->createOrderShippingAddress($values = [
                'country_id' => 'BE',
                'line_1' => 'line-1',
                'line_2' => 'line-2',
                'postal_code' => 'postal-code',
                'city' => 'city',
            ]),
        );

        $this->orderRepository->save($order);

        $this->merchantOrderApplication->updateShippingAddress(new UpdateShippingAddress(
            $order->orderId->get(),
            ...array_values($values)
        ), []);

        $this->assertCount(0, $this->eventDispatcher->releaseDispatchedEvents());
    }
}
