<?php

namespace Thinktomorrow\Trader\Tests\Features\Orders;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\DiscountFactory;
use Thinktomorrow\Trader\Orders\Application\OrderAssembler;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Orders\Domain\Purchasable;
use Thinktomorrow\Trader\Tests\Features\FeatureTestCase;
use Thinktomorrow\Trader\Tests\InMemoryContainer;
use Thinktomorrow\Trader\Tests\Unit\Stubs\PurchasableStub;

class OrderAssemblerTest extends FeatureTestCase
{
    private $assembler;

    public function setUp()
    {
        parent::setUp();

        $this->assembler = $this->container(OrderAssembler::class);
    }

    /** @test */
    function it_can_assemble_an_order_with_proper_calculations()
    {
        $this->addDummyOrder(1);
        $order = $this->assembler->assemble(1);

        // TOTALS
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals(Money::EUR(2105),$order->subtotal());
        $this->assertEquals(Money::EUR(2105)->multiply(0.3),$order->discountTotal());
        $this->assertEquals(Money::EUR(15),$order->shippingTotal());
        $this->assertEquals(Money::EUR(10),$order->paymentTotal());
        $this->assertEquals(Money::EUR(2105)->subtract(Money::EUR(2105)->multiply(0.3))->add(Money::EUR(25)),$order->total());

        // TAX
        $this->assertEquals(Percentage::fromPercent(21), $order->taxPercentage());
        $this->assertCount(2,$order->taxRates());
        $this->assertEquals(Money::EUR(211),$order->taxRates()[10]['tax']);
        $this->assertEquals(Money::EUR(5),$order->taxRates()[21]['tax']);
        $this->assertEquals(Money::EUR(216),$order->tax()); // 211 + 5
    }

    /** @test */
    function it_can_assemble_an_order_with_proper_applications()
    {
        $this->addDummyOrder(1);
        $order = $this->assembler->assemble(1);

        $this->assertCount(1,$order->discounts());
    }

    /** @test */
    function items_have_the_expected_properties()
    {
        $this->addDummyOrder(1);
        $order = $this->assembler->assemble(1);

        $this->assertCount(2,$order->items());

        $firstItem = $order->items()[1];

        $this->assertInstanceOf(Purchasable::class, $firstItem->purchasable());
        $this->assertEquals(1, $firstItem->quantity());
    }

    /** @test */
    function it_should_only_assemble_ongoing_orders()
    {

    }

    /** @test */
    function it_should_not_emit_events_during_assembly()
    {

    }

    /** @test */
    function non_allowed_discount_should_be_reapplied()
    {

    }

    private function addDummyOrder($id)
    {
        $order = new Order(OrderId::fromInteger($id));
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(1, [], Cash::make(505), Percentage::fromPercent(10))));
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(2, [], Cash::make(1000), Percentage::fromPercent(10), Cash::make(800))), 2);
        $order->setShippingTotal(Cash::make(15));
        $order->setPaymentTotal(Cash::make(10));

        $discount = (new DiscountFactory(new InMemoryContainer()))->create(1, 'percentage_off', [], ['percentage' => Percentage::fromPercent(30)]);
        $discount->apply($order);

        $order->setTaxPercentage(Percentage::fromPercent(21));

        $this->container('orderRepository')->add($order);
    }
}