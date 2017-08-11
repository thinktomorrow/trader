<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Domain\OrderId;
use Thinktomorrow\Trader\Order\Domain\OrderState;
use Thinktomorrow\Trader\Order\Ports\Persistence\InMemoryOrderRepository;
use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePurchasable;

class OrderRepositoryTest extends UnitTestCase
{
    /** @test */
    function it_can_find_an_order()
    {
        $order = $this->makeOrder(0, 3);
        $repo = new InMemoryOrderRepository();

        $repo->add($order);

        $this->assertEquals($order, $repo->find(OrderId::fromInteger(3)));
    }

    /** @test */
    function it_throws_exception_if_order_does_not_exist()
    {
        $this->setExpectedException(\RuntimeException::class);

        $repo = new InMemoryOrderRepository();
        $repo->find(OrderId::fromInteger(9));
    }

    /** @test */
    function it_returns_raw_values_for_merchant_order()
    {
        $order = $this->makeOrder(0, 3);
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(1,[],Money::EUR(50),Percentage::fromPercent(21))));
        $order->setShipmentTotal(Money::EUR(15));
        $order->setPaymentTotal(Money::EUR(10));

        $repo = new InMemoryOrderRepository();
        $repo->add($order);
        $values = $repo->getValues(OrderId::fromInteger(3));

        $this->assertInternalType('array',$values);

        // Check all expected attributes are given
        $this->assertArrayHasKey('total',$values);
        $this->assertArrayHasKey('subtotal',$values);
        $this->assertArrayHasKey('payment_total',$values);
        $this->assertArrayHasKey('shipment_total',$values);
        $this->assertArrayHasKey('tax',$values);
        $this->assertArrayHasKey('tax_rates',$values);
        $this->assertArrayHasKey('reference',$values);
        $this->assertArrayHasKey('confirmed_at',$values);
        $this->assertArrayHasKey('state',$values);

        // Check all values are of the correct format
        $this->assertEquals(Money::EUR(75), $values['total']);
        $this->assertEquals(Money::EUR(50), $values['subtotal']);
        $this->assertEquals(Money::EUR(10), $values['payment_total']);
        $this->assertEquals(Money::EUR(15), $values['shipment_total']);
        $this->assertEquals(Money::EUR(11), $values['tax']);
        $this->assertInternalType('array', $values['tax_rates']);
        $this->assertEquals(3,$values['reference']);
        $this->assertEquals(new \DateTime('@'.strtotime('-1days')),$values['confirmed_at']);
        $this->assertEquals(OrderState::STATE_NEW, $values['state']);
    }

    /** @test */
    function merchant_order_has_tax_rates_grouped_by_rate()
    {
        $order = $this->makeOrder(0, 3);
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(1,[],Money::EUR(50),Percentage::fromPercent(21))));
        $order->setShipmentTotal(Money::EUR(15));
        $order->setPaymentTotal(Money::EUR(10));

        $repo = new InMemoryOrderRepository();
        $repo->add($order);
        $values = $repo->getValues(OrderId::fromInteger(3));

        $testedTaxRates = false;
        foreach($values['tax_rates'] as $tax_rate)
        {
            $testedTaxRates = true;
            $this->assertInstanceOf(Percentage::class, $tax_rate['percent']);
            $this->assertInstanceOf(Money::class, $tax_rate['tax']);
        }
        $this->assertTrue($testedTaxRates,'tax_rates value remains untested. Make sure to at least provide one entry.');
    }
}