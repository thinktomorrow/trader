<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order\Merchant;

use Tests\Acceptance\Cart\CartContext;
use Thinktomorrow\Trader\Application\Order\Merchant\UpdateShippingData;

class UpdateShippingDataTest extends CartContext
{
    public function test_merchant_can_change_shipping_data()
    {
        $order = $this->orderContext->createDefaultOrder();
        $shipping = $order->getShippings()[0];

        $this->orderContext->apps()->merchantOrderApplication()->updateShippingData(new UpdateShippingData(
            $order->orderId->get(),
            $shipping->shippingId->get(),
            ['foo' => 'bar']
        ));

        $order = $this->orderContext->findOrder($order->orderId);

        $this->assertEquals(array_merge($shipping->getData(), [
            'foo' => 'bar',
        ]), $order->getShippings()[0]->getData());
    }
}
