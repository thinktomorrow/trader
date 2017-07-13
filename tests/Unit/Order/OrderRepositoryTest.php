<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use Thinktomorrow\Trader\Order\Domain\OrderId;
use Thinktomorrow\Trader\Order\Ports\Persistence\InMemoryOrderRepository;

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
        $repo = new InMemoryOrderRepository();
        $order = $this->makeOrder(10, 3);
        $repo->add($order);

        $values = $repo->getValuesForMerchantOrder(OrderId::fromInteger(3));

        $this->assertInternalType('array',$values);

        // Check all expected attributes are given
        $this->assertArrayHasKey('total',$values);
        $this->assertArrayHasKey('subtotal',$values);
        $this->assertArrayHasKey('payment_total',$values);
        $this->assertArrayHasKey('shipment_total',$values);

        // Check all values are of the correct format
        $this->assertEquals(Money::EUR(10), $values['total']);
        $this->assertEquals(Money::EUR(10), $values['subtotal']);
        $this->assertEquals(Money::EUR(0), $values['payment_total']);
        $this->assertEquals(Money::EUR(0), $values['shipment_total']);

//        'total' => Money::EUR(1290),
//            'subtotal' => Money::EUR(900),
//            'payment_total' => Money::EUR(0),
//            'shipment_total' => Money::EUR(50),
//            'tax' => Money::EUR(30),
//            'tax_rate' => Percentage::fromPercent(21),
//            'reference' => 'a782820ZIsksa',
//            'confirmed_at' => (new \DateTime('@'.strtotime('-9days'))),
//            'state' => 'confirmed',
//            'items' => [
//        [
//            'name' => 'dude',
//            'sku' => '123490',
//            'stock' => 5,
//            'stock_warning' => false,
//            'saleprice' => Money::EUR(120),
//            'quantity' => 2,
//            'total' => Money::EUR(240),
//        ],
//        [
//            'name' => 'tweede',
//            'sku' => '1293939',
//            'stock' => 1,
//            'stock_warning' => true,
//            'saleprice' => Money::EUR(820),
//            'total' => Money::EUR(820),
//        ],
//    ],
    }
}