<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order\Merchant;

use Tests\Acceptance\Cart\CartContext;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Application\Order\Merchant\UpdateShippingData;
use Thinktomorrow\Trader\Application\Order\Merchant\MerchantOrderApplication;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;

class UpdateShippingDataTest extends CartContext
{
    private MerchantOrderApplication $merchantOrderApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = new InMemoryOrderRepository();

        $this->merchantOrderApplication = new MerchantOrderApplication(
            $this->orderRepository,
            new EventDispatcherSpy(),
        );
    }

    public function test_merchant_can_change_shipping_data()
    {
        $order = $this->createOrder(['order_id' => 'xxx'], [], [], [
            $shipping = $this->createOrderShipping(),
        ]);

        $this->orderRepository->save($order);

        $this->merchantOrderApplication->updateShippingData(new UpdateShippingData(
            $order->orderId->get(),
            $shipping->shippingId->get(),
            ['foo' => 'bar']
        ));

        $order = $this->orderRepository->find($order->orderId);

        $this->assertEquals(['foo' => 'bar'], $order->getShippings()[0]->getData());
    }
}
