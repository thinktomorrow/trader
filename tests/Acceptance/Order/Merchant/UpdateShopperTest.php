<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order\Merchant;

use Tests\Acceptance\Cart\CartContext;
use Thinktomorrow\Trader\Application\Order\Merchant\UpdateShopper;
use Thinktomorrow\Trader\Domain\Model\Order\Events\Merchant\ShopperUpdatedByMerchant;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;

class UpdateShopperTest extends CartContext
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = new InMemoryOrderRepository();
    }

    public function test_merchant_can_change_shopper()
    {
        $order = $this->createOrder(
            ['order_id' => 'xxx'],
            [],
            [],
            [],
            [],
            null,
            null,
            $this->createOrderShopper([
                'email' => 'ben@thinktomorrow.be',
                'locale' => 'en_GB',
                'data' => json_encode(['foo' => 'bar']),
            ])
        );

        $this->orderRepository->save($order);

        $this->merchantOrderApplication->updateShopper(new UpdateShopper(
            $order->orderId->get(),
            'ben-changed@thinktomorrow.be',
            false,
            'nl',
            ['foo' => 'baz', 'foz' => 'boss']
        ), []);

        $order = $this->orderRepository->find($order->orderId);

        $this->assertEquals('ben-changed@thinktomorrow.be', $order->getShopper()->getEmail()->get());
        $this->assertEquals('nl', $order->getShopper()->getLocale()->get());
        $this->assertFalse($order->getShopper()->isBusiness());
        $this->assertEquals(['foo' => 'baz', 'foz' => 'boss', 'customer_id' => $order->getShopper()->getCustomerId()], $order->getShopper()->getData());

        $this->assertEquals(new ShopperUpdatedByMerchant($order->orderId, [
            'email' => ['old' => 'ben@thinktomorrow.be', 'new' => 'ben-changed@thinktomorrow.be'],
            'locale' => ['old' => 'en-gb', 'new' => 'nl'],
            'foo' => ['old' => 'bar', 'new' => 'baz'],
            'foz' => ['old' => null, 'new' => 'boss'],
        ], []), $this->eventDispatcher->releaseDispatchedEvents()[1]);
    }
}
