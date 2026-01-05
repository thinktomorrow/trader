<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order\Merchant;

use Tests\Acceptance\Cart\CartContext;
use Thinktomorrow\Trader\Application\Order\Merchant\AddLogEntry;

class AddLogEntryTest extends CartContext
{
    public function test_merchant_can_change_shipping_data()
    {
        $order = $this->orderContext->createOrder();

        $this->orderContext->apps()->merchantOrderApplication()->addLogEntry(new AddLogEntry(
            $order->orderId->get(),
            'transition.confirmed', // transition.confirmed, transition.paid, notification.delay
            ['foo' => 'bar']
        ));

        $order = $this->orderContext->findOrder($order->orderId);

        $this->assertCount(1, $order->getOrderEvents());
        $this->assertEquals('transition.confirmed', $order->getOrderEvents()[0]->getEvent());
    }
}
