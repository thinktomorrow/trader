<?php

declare(strict_types=1);

namespace Tests\Acceptance\Order\Merchant;

use Tests\Acceptance\Cart\CartContext;
use Thinktomorrow\Trader\Application\Order\Merchant\UpdateShopper;
use Thinktomorrow\Trader\Domain\Model\Order\Events\Merchant\ShopperUpdatedByMerchant;

class UpdateShopperTest extends CartContext
{
    public function test_merchant_can_change_shopper()
    {
        $order = $this->orderContext->createDefaultOrder();
        $shopper = $order->getShopper();

        $this->orderContext->apps()->merchantOrderApplication()->updateShopper(new UpdateShopper(
            $order->orderId->get(),
            'ben-changed@thinktomorrow.be',
            false,
            'nl',
            ['foo' => 'baz', 'foz' => 'boss']
        ), []);

        $order = $this->orderContext->findOrder($order->orderId);

        $this->assertEquals('ben-changed@thinktomorrow.be', $order->getShopper()->getEmail()->get());
        $this->assertEquals('nl', $order->getShopper()->getLocale()->get());
        $this->assertFalse($order->getShopper()->isBusiness());
        $this->assertEquals(array_merge($shopper->getData(), [
            'foo' => 'baz', 'foz' => 'boss']), $order->getShopper()->getData());

        $this->assertEquals(new ShopperUpdatedByMerchant($order->orderId, [
            'email' => ['old' => 'order-aaa-shopper-aaa@thinktomorrow.be', 'new' => 'ben-changed@thinktomorrow.be'],
            'locale' => ['old' => 'nl-be', 'new' => 'nl'],
            'foo' => ['old' => null, 'new' => 'baz'],
            'foz' => ['old' => null, 'new' => 'boss'],
        ], []), $this->orderContext->apps()->getEventDispatcher()->releaseDispatchedEvents()[1]);
    }
}
