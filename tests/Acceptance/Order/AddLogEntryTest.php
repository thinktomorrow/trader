<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order;

use Tests\Acceptance\Cart\CartContext;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Application\Order\Merchant\AddLogEntry;
use Thinktomorrow\Trader\Application\Order\Merchant\ChangeShippingData;
use Thinktomorrow\Trader\Application\Order\Merchant\MerchantOrderApplication;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;

class AddLogEntryTest extends CartContext
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
        $order = $this->createOrder(['order_id' => 'xxx']);
        $this->orderRepository->save($order);

        $this->merchantOrderApplication->addLogEntry(new AddLogEntry(
            $order->orderId->get(),
            'transition.confirmed', // transition.confirmed, transition.paid, notification.delay
            ['foo' => 'bar']
        ));

        $order = $this->orderRepository->find($order->orderId);

        $this->assertCount(1, $order->getLogEntries());
        $this->assertEquals('transition.confirmed', $order->getLogEntries()[0]->getEvent());
    }
}
