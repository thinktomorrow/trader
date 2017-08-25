<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Orders\Domain\OrderReference;
use Thinktomorrow\Trader\Orders\Domain\OrderState;
use Thinktomorrow\Trader\Orders\Ports\Persistence\InMemoryOrderRepository;
use Thinktomorrow\Trader\Tests\Unit\Stubs\PurchasableStub;

class OrderRepositoryTest extends UnitTestCase
{
    /** @test */
    public function it_can_find_an_order()
    {
        $order = $this->makeOrder(0, 3);
        $repo = new InMemoryOrderRepository();

        $repo->add($order);

        $this->assertEquals($order, $repo->find(OrderId::fromInteger(3)));
    }

    /** @test */
    public function it_can_get_next_identity()
    {
        $repo = new InMemoryOrderRepository();

        $id = $repo->nextIdentity();

        $this->assertInstanceOf(OrderId::class, $id);

        // Check valid UUID
        $UUIDv4 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
        $this->assertTrue((bool) preg_match($UUIDv4, $id->get()));
    }

    /** @test */
    public function it_can_get_next_reference()
    {
        $repo = new InMemoryOrderRepository();

        $reference = $repo->nextReference();

        $this->assertInstanceOf(OrderReference::class, $reference);

        // Check valid UUID
        $UUIDv4 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
        $this->assertTrue((bool) preg_match($UUIDv4, $reference->get()));
    }

    /** @test */
    public function it_throws_exception_if_order_does_not_exist()
    {
        $this->setExpectedException(\RuntimeException::class, 'No order found');

        $repo = new InMemoryOrderRepository();
        $repo->find(OrderId::fromInteger(9));
    }

    /** @test */
    public function it_returns_raw_values_for_merchant_order()
    {
        $order = $this->makeOrder(0, 3);
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(1, [], Money::EUR(50), Percentage::fromPercent(21))));
        $order->setShippingTotal(Money::EUR(15));
        $order->setPaymentTotal(Money::EUR(10));

        $repo = new InMemoryOrderRepository();
        $repo->add($order);
        $values = $repo->getValues(OrderId::fromInteger(3));

        $this->assertInternalType('array', $values);

        // Check all expected attributes are given
        $this->assertArrayHasKey('total', $values);
        $this->assertArrayHasKey('subtotal', $values);
        $this->assertArrayHasKey('payment_total', $values);
        $this->assertArrayHasKey('shipment_total', $values);
        $this->assertArrayHasKey('tax', $values);
        $this->assertArrayHasKey('tax_rates', $values);
        $this->assertArrayHasKey('reference', $values);
        $this->assertArrayHasKey('confirmed_at', $values);
        $this->assertArrayHasKey('state', $values);

        // Check all values are of the correct format
        $this->assertEquals(Money::EUR(75), $values['total']);
        $this->assertEquals(Money::EUR(50), $values['subtotal']);
        $this->assertEquals(Money::EUR(10), $values['payment_total']);
        $this->assertEquals(Money::EUR(15), $values['shipment_total']);
        $this->assertEquals(Money::EUR(11), $values['tax']);
        $this->assertInternalType('array', $values['tax_rates']);
        $this->assertEquals(3, $values['reference']);
        $this->assertEquals(new \DateTime('@'.strtotime('-1days')), $values['confirmed_at']);
        $this->assertEquals(OrderState::STATE_NEW, $values['state']);
    }

    /** @test */
    public function merchant_order_has_tax_rates_grouped_by_rate()
    {
        $order = $this->makeOrder(0, 3);
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(1, [], Money::EUR(50), Percentage::fromPercent(21))));
        $order->setShippingTotal(Money::EUR(15));
        $order->setPaymentTotal(Money::EUR(10));

        $repo = new InMemoryOrderRepository();
        $repo->add($order);
        $values = $repo->getValues(OrderId::fromInteger(3));

        $testedTaxRates = false;
        foreach ($values['tax_rates'] as $tax_rate) {
            $testedTaxRates = true;
            $this->assertInstanceOf(Percentage::class, $tax_rate['percent']);
            $this->assertInstanceOf(Money::class, $tax_rate['tax']);
        }
        $this->assertTrue($testedTaxRates, 'tax_rates value remains untested. Make sure to at least provide one entry.');
    }
}
