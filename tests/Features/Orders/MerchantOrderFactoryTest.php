<?php

namespace Thinktomorrow\Trader\Tests\Features;

use Assert\Assertion;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\DiscountFactory;
use Thinktomorrow\Trader\Orders\Application\Reads\Expanded\MerchantOrderFactory;
use Thinktomorrow\Trader\Orders\Domain\Item as DomainItem;
use Thinktomorrow\Trader\Orders\Domain\Order as DomainOrder;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Orders\Ports\Reads\ExpandedItem;
use Thinktomorrow\Trader\Orders\Ports\Reads\ExpandedOrder;
use Thinktomorrow\Trader\Tests\InMemoryContainer;
use Thinktomorrow\Trader\Tests\Unit\Stubs\PurchasableStub;

class MerchantOrderFactoryTest extends FeatureTestCase
{
    /** @test */
    public function it_can_resolve_order_for_merchant()
    {
        $this->assertInstanceOf(ExpandedOrder::class, $this->assembleMerchantOrder());
    }

    /** @test */
    public function it_resolves_merchant_order_with_expected_properties()
    {
        $merchantOrder = $this->assembleMerchantOrder();

        $this->assertEquals('&euro;21.05', $merchantOrder->subtotal());
        $this->assertEquals('&euro;6.32', $merchantOrder->discountTotal());
        $this->assertEquals('&euro;0.15', $merchantOrder->shippingTotal());
        $this->assertEquals('&euro;0.10', $merchantOrder->paymentTotal());
        $this->assertEquals('&euro;14.98', $merchantOrder->total());

        $this->assertEquals('&euro;2.16', $merchantOrder->tax()); // TODO: discount is not taken into account yet.
    }

    /** @test */
    public function merchant_order_contains_item_presenters()
    {
        $merchantOrder = $this->assembleMerchantOrder();

        $this->assertCount(2, $merchantOrder->items());
        $this->assertTrue(Assertion::allIsInstanceOf($merchantOrder->items(), ExpandedItem::class));
    }

    /**
     * @return ExpandedOrder
     */
    private function assembleMerchantOrder(): ExpandedOrder
    {
        $this->addDummyOrder(1);

        $assembler = new MerchantOrderFactory($this->container('orderRepository'), $this->container);
        $merchantOrder = $assembler->create(1);

        return $merchantOrder;
    }

    private function addDummyOrder($id)
    {
        $order = new DomainOrder(OrderId::fromInteger($id));
        $order->items()->add(DomainItem::fromPurchasable(new PurchasableStub(1, [], Cash::make(505), Percentage::fromPercent(10))));
        $order->items()->add(DomainItem::fromPurchasable(new PurchasableStub(2, [], Cash::make(1000), Percentage::fromPercent(10), Cash::make(800))), 2);
        $order->setShippingTotal(Cash::make(15));
        $order->setPaymentTotal(Cash::make(10));

        $discount = (new DiscountFactory(new InMemoryContainer()))->create(1, 'percentage_off', [], ['percentage' => Percentage::fromPercent(30)]);
        $discount->apply($order);

        $order->setTaxPercentage(Percentage::fromPercent(21));

        $this->container('orderRepository')->add($order);
    }
}
