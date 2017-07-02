<?php

namespace Thinktomorrow\Trader\Tests\Features;

use Money\Money;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Shipment\Application\ApplyShippingRuleToOrder;
use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePurchasable;
use Thinktomorrow\Trader\Tests\Unit\UnitTestCase;

class ApplyShippingRuleToOrderTest extends UnitTestCase
{
    /** @test */
    function empty_order_means_no_shipping_costs()
    {
        // Set up order, items and shipment
        $order = $this->makeOrder();

        // Apply shipment to order
        $handler = new ApplyShippingRuleToOrder();
        $handler->handle($order);

        $this->assertEquals(Money::EUR(0),$order->shipmentTotal());
    }

    /** @test */
    function it_can_apply_shippingcost_to_order()
    {
        // Setup shipment method


        // Set up order, items and shipment
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(20,[],Money::EUR(240))));

        // Apply shipment to order
        $handler = new ApplyShippingRuleToOrder();
        $handler->handle($order);

        $this->assertEquals(Money::EUR(0),$order->shipmentTotal());
        $this->assertEquals(Money::EUR(240), $order->total());
    }
}