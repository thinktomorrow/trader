<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Orders\Domain\CustomerId;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\ItemCollection;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;

class OrderTest extends UnitTestCase
{
    /** @test */
    public function orderId_is_a_valid_identifier()
    {
        $orderId = OrderId::fromInteger(2);
        $orderId2 = OrderId::fromString(2);

        $this->assertEquals(2, $orderId->get());
        $this->assertTrue($orderId->equals($orderId2));
    }

    /** @test */
    function it_can_set_a_persistence_id()
    {
        $order = new Order(OrderId::fromInteger(1));
        $order->setPersistenceId(2);

        $this->assertEquals(2,$order->persistenceId());
    }

    /** @test */
    function it_can_check_if_there_is_a_persistence_id()
    {
        $order = new Order(OrderId::fromInteger(1));
        $this->assertFalse($order->isPersisted());

        $order->setPersistenceId(2);
        $this->assertTrue($order->isPersisted());
    }

    /** @test */
    function it_can_set_a_customer()
    {
        $order = new Order(OrderId::fromInteger(1));
        $order->setCustomerId(CustomerId::fromString(2));

        $this->assertEquals(CustomerId::fromString(2),$order->customerId());
    }

    /** @test */
    function it_can_check_if_there_is_a_customer()
    {
        $order = new Order(OrderId::fromInteger(1));
        $this->assertFalse($order->hasCustomer());

        $order->setCustomerId(CustomerId::fromString(2));
        $this->assertTrue($order->hasCustomer());
    }

    /** @test */
    function retrieving_customer_when_there_is_none_fails()
    {
        $this->expectException(\RuntimeException::class,'customer');

        $order = new Order(OrderId::fromInteger(1));

        $order->customerId();
    }

    /** @test */
    public function it_starts_with_empty_itemcollection()
    {
        $order = $this->makeOrder();

        $this->assertInstanceOf(ItemCollection::class, $order->items());
        $this->assertCount(0, $order->items());
    }

    /** @test */
    public function it_has_shipment_cost()
    {
        $order = $this->makeOrder();

        $this->assertEquals(Money::EUR(0), $order->shippingTotal());

        $order->setShippingTotal(Money::EUR(120));
        $this->assertEquals(Money::EUR(120), $order->shippingTotal());
    }

    /** @test */
    public function it_returns_the_tax()
    {
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(1, [], Money::EUR(200), Percentage::fromPercent(20))));

        $this->assertEquals(Money::EUR(40), $order->tax());
        $this->assertEquals([
            20 => [
                'percent' => Percentage::fromPercent(20),
                'tax'     => Money::EUR(40),
                'total'   => Money::EUR(200),
            ],
        ], $order->taxRates());
    }

    /** @test */
    public function it_can_set_shipping_address()
    {
        $order = $this->makeOrder();

        $order->setShippingAddress(['country_key' => 'BE']);
        $this->assertEquals(['country_key' => 'BE'], $order->shippingAddress());
        $this->assertEquals('BE', $order->shippingAddress('country_key'));
    }

    /** @test */
    public function it_sums_up_all_given_tax_rates()
    {
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(1, [], Money::EUR(200), Percentage::fromPercent(20))));
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(2, [], Money::EUR(100), Percentage::fromPercent(6))));
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(3, [], Money::EUR(100), Percentage::fromPercent(6))));

        $this->assertEquals(Money::EUR(40)->add(Money::EUR(12)), $order->tax());
        $this->assertEquals([
            20 => [
                'percent' => Percentage::fromPercent(20),
                'tax'     => Money::EUR(40),
                'total'   => Money::EUR(200),
            ],
            6 => [
                'percent' => Percentage::fromPercent(6),
                'tax'     => Money::EUR(12),
                'total'   => Money::EUR(200),
            ],
        ], $order->taxRates());
    }

    /** @test */
    public function it_sums_up_the_taxes()
    {
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(1, [], Money::EUR(300), Percentage::fromPercent(21))));
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(2, [], Money::EUR(50), Percentage::fromPercent(21))));
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(3, [], Money::EUR(1050), Percentage::fromPercent(21))));

        $this->assertEquals(Money::EUR(294), $order->tax());
    }
}