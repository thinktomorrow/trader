<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Domain\ItemCollection;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Order\Domain\OrderId;
use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePurchasable;

class OrderTest extends UnitTestCase
{
    /** @test */
    function orderId_is_a_valid_identifier()
    {
        $orderId = OrderId::fromInteger(2);

        $this->assertEquals(2,$orderId->get());
    }

    /** @test */
    function it_starts_with_empty_itemcollection()
    {
        $order = $this->makeOrder();

        $this->assertInstanceOf(ItemCollection::class, $order->items());
        $this->assertCount(0,$order->items());
    }

    /** @test */
    function it_has_shipment_cost()
    {
        $order = $this->makeOrder();

        $this->assertEquals(Money::EUR(0), $order->shipmentTotal());

        $order->setShipmentTotal(Money::EUR(120));
        $this->assertEquals(Money::EUR(120), $order->shipmentTotal());
    }

    /** @test */
    function it_returns_the_tax()
    {
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(1,[],Money::EUR(200),Percentage::fromPercent(20))));

        $this->assertEquals(Money::EUR(40),$order->tax());
        $this->assertEquals([
            20 => [
                'percent' => Percentage::fromPercent(20),
                'tax' => Money::EUR(40)
            ]
        ],$order->taxRates());
    }

    /** @test */
    function it_sums_up_all_given_tax_rates()
    {
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(1,[],Money::EUR(200),Percentage::fromPercent(20))));
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(2,[],Money::EUR(100),Percentage::fromPercent(6))));
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(3,[],Money::EUR(100),Percentage::fromPercent(6))));

        $this->assertEquals(Money::EUR(40)->add(Money::EUR(12)),$order->tax());
        $this->assertEquals([
            20 => [
                'percent' => Percentage::fromPercent(20),
                'tax' => Money::EUR(40)
            ],
            6 => [
                'percent' => Percentage::fromPercent(6),
                'tax' => Money::EUR(12)
            ]
        ],$order->taxRates());
    }

    /** @test */
    function it_sums_up_the_taxes()
    {
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(1,[],Money::EUR(300),Percentage::fromPercent(21))));
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(2,[],Money::EUR(50),Percentage::fromPercent(21))));
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(3,[],Money::EUR(1050),Percentage::fromPercent(21))));

        $this->assertEquals(Money::EUR(294),$order->tax());
    }
}