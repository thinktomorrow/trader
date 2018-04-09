<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Common\Config;
use Thinktomorrow\Trader\Common\Price\Percentage;
use Thinktomorrow\Trader\Countries\CountryId;
use Thinktomorrow\Trader\Orders\Domain\CustomerId;
use Thinktomorrow\Trader\Orders\Domain\ItemCollection;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;

class OrderTest extends TestCase
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
    public function it_can_set_a_persistence_id()
    {
        $order = new Order(OrderId::fromInteger(1));
        $order->setPersistenceId(2);

        $this->assertEquals(2, $order->persistenceId());
    }

    /** @test */
    public function it_can_check_if_there_is_a_persistence_id()
    {
        $order = new Order(OrderId::fromInteger(1));
        $this->assertFalse($order->isPersisted());

        $order->setPersistenceId(2);
        $this->assertTrue($order->isPersisted());
    }

    /** @test */
    public function it_can_set_a_customer()
    {
        $order = new Order(OrderId::fromInteger(1));
        $order->setCustomerId(CustomerId::fromString(2));

        $this->assertEquals(CustomerId::fromString(2), $order->customerId());
    }

    /** @test */
    public function it_can_check_if_there_is_a_customer()
    {
        $order = new Order(OrderId::fromInteger(1));
        $this->assertFalse($order->hasCustomer());

        $order->setCustomerId(CustomerId::fromString(2));
        $this->assertTrue($order->hasCustomer());
    }

    /** @test */
    public function retrieving_customer_when_there_is_none_fails()
    {
        $this->expectException(\RuntimeException::class, 'customer');

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
        $order->items()->add($this->getItem(null, Percentage::fromPercent(20), new PurchasableStub(1, [], Money::EUR(200))));

        $this->assertEquals(Money::EUR(40), $order->taxTotal());
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
    public function shipping_country_is_retrieved_as_country_id()
    {
        $order = $this->makeOrder();
        $order->setShippingAddress(['country_key' => 'BE']);
        $this->assertEquals(CountryId::fromIsoString('BE'), $order->shippingCountryId());
    }

    /** @test */
    public function it_can_set_billing_address()
    {
        $order = $this->makeOrder();

        $order->setBillingAddress(['country_key' => 'NL']);
        $this->assertEquals(['country_key' => 'NL'], $order->billingAddress());
        $this->assertEquals('NL', $order->billingAddress('country_key'));
    }

    /** @test */
    public function billing_country_is_retrieved_as_country_id()
    {
        $order = $this->makeOrder();
        $order->setBillingAddress(['country_key' => 'NL']);
        $this->assertEquals(CountryId::fromIsoString('NL'), $order->billingCountryId());
    }

    /** @test */
    public function fallback_country_id_can_be_set_explicitly()
    {
        $order = $this->makeOrder();
        $order->setFallbackCountryId(CountryId::fromIsoString('NL'));
        $this->assertEquals(CountryId::fromIsoString('NL'), $order->fallbackCountryId());
    }

    /** @test */
    public function if_fallback_country_is_not_set_the_default_is_taken()
    {
        (new Config())->set('country_id', 'DK');

        $order = $this->makeOrder();
        $this->assertEquals(CountryId::fromIsoString('DK'), $order->fallbackCountryId());
    }

    /** @test */
    public function it_sums_up_all_given_tax_rates()
    {
        $order = $this->makeOrder();
        $order->items()->add($this->getItem(null, Percentage::fromPercent(20), new PurchasableStub(1, [], Money::EUR(200))));
        $order->items()->add($this->getItem(null, Percentage::fromPercent(6), new PurchasableStub(2, [], Money::EUR(100))));
        $order->items()->add($this->getItem(null, Percentage::fromPercent(6), new PurchasableStub(3, [], Money::EUR(100))));

        $this->assertEquals(Money::EUR(40)->add(Money::EUR(12)), $order->taxTotal());
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
        $order->items()->add($this->getItem(null, Percentage::fromPercent(21), new PurchasableStub(1, [], Money::EUR(300))));
        $order->items()->add($this->getItem(null, Percentage::fromPercent(21), new PurchasableStub(2, [], Money::EUR(50))));
        $order->items()->add($this->getItem(null, Percentage::fromPercent(21), new PurchasableStub(3, [], Money::EUR(1050))));

        $this->assertEquals(Money::EUR(294), $order->taxTotal());
    }
}
