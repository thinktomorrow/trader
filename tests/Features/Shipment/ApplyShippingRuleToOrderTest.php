<?php

namespace Thinktomorrow\Trader\Tests\Features;

use Money\Money;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Ports\Persistence\InMemoryOrderRepository;
use Thinktomorrow\Trader\Shipment\Application\ApplyShippingMethodToOrder;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethod;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethodId;
use Thinktomorrow\Trader\Shipment\Domain\ShippingRuleFactory;
use Thinktomorrow\Trader\Shipment\Ports\Persistence\InMemoryShippingMethodRepository;
use Thinktomorrow\Trader\Tests\InMemoryContainer;
use Thinktomorrow\Trader\Tests\Unit\Stubs\PurchasableStub;
use Thinktomorrow\Trader\Tests\Unit\UnitTestCase;

class ApplyShippingRuleToOrderTest extends UnitTestCase
{
    private $orderRepository;
    private $shippingMethodRepository;

    public function setUp()
    {
        parent::setUp();

        $this->orderRepository = new InMemoryOrderRepository();
        $this->shippingMethodRepository = new InMemoryShippingMethodRepository();
    }

    protected function makeHandler()
    {
        return new ApplyShippingMethodToOrder(
            $this->orderRepository,
            $this->shippingMethodRepository
        );
    }

    /** @test */
    public function empty_order_means_no_shipping_costs()
    {
        // Set up order, items and shipment
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(20, [], Money::EUR(240))));

        $this->orderRepository->add($order);
        $this->shippingMethodRepository->add(new ShippingMethod(ShippingMethodId::fromInteger(2), []));

        // Apply shipment to order
        $this->makeHandler()->handle($order->id(), ShippingMethodId::fromInteger(2));

        $this->assertEquals(Money::EUR(0), $order->shipmentTotal());
        $this->assertEquals(Money::EUR(240), $order->total());
    }

    /** @test */
    public function it_can_apply_shippingcost_to_order()
    {
        // Set up order, items and shipment
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(20, [], Money::EUR(240))));

        $this->orderRepository->add($order);
        $this->shippingMethodRepository->add(new ShippingMethod(ShippingMethodId::fromInteger(2), [
            (new ShippingRuleFactory(new InMemoryContainer()))->create(1, [], [
                'amount' => Money::EUR(24),
            ]),
        ]));

        // Apply shipment to order
        $this->makeHandler()->handle($order->id(), ShippingMethodId::fromInteger(2));

        $this->assertEquals(Money::EUR(24), $order->shipmentTotal());
        $this->assertEquals(Money::EUR(264), $order->total());
    }
}
