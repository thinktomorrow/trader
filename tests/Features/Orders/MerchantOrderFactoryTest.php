<?php

namespace Thinktomorrow\Trader\Tests\Features;

use Assert\Assertion;
use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\DiscountFactory;
use Thinktomorrow\Trader\Orders\Domain\CustomerId;
use Thinktomorrow\Trader\Orders\Domain\Item as DomainItem;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\Order as DomainOrder;
use Thinktomorrow\Trader\Orders\Domain\OrderId;

use Thinktomorrow\Trader\Orders\Application\Reads\Merchant\MerchantOrderFactory;
use Thinktomorrow\Trader\Orders\Application\Reads\Merchant\MerchantItem as MerchantItemContract;
use Thinktomorrow\Trader\Orders\Application\Reads\Merchant\MerchantOrder as MerchantOrderContract;
use Thinktomorrow\Trader\Orders\Ports\Read\MerchantItem;
use Thinktomorrow\Trader\Orders\Ports\Read\MerchantOrder;

use Thinktomorrow\Trader\Orders\Ports\Read\MerchantOrderResource;
use Thinktomorrow\Trader\Tests\InMemoryContainer;
use Thinktomorrow\Trader\Tests\Unit\Stubs\PurchasableStub;

class MerchantOrderFactoryTest extends FeatureTestCase
{
    /** @test */
    public function it_can_resolve_order_for_merchant()
    {
        $this->assertInstanceOf(MerchantOrderContract::class, $this->assembleMerchantOrder());
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
        $this->assertTrue(Assertion::allIsInstanceOf($merchantOrder->items(), MerchantItemContract::class));
    }

    /** @test */
    public function merchant_order_has_tax_rates_grouped_by_rate()
    {
        $this->markTestIncomplete();

        $order = $this->addDummyOrder(2);
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(1, [], Money::EUR(50), Percentage::fromPercent(21))));
        $order->setShippingTotal(Money::EUR(15));
        $order->setPaymentTotal(Money::EUR(10));

        $merchantOrder = (new MerchantOrderFactory($this->container('orderRepository'), $this->container))
            ->create(new MerchantOrderResource($order));

        $testedTaxRates = false;
        foreach ($merchantOrder->taxRates() as $tax_rate) {
            $testedTaxRates = true;
            $this->assertInstanceOf(Percentage::class, $tax_rate['percent']);
            $this->assertInstanceOf(Money::class, $tax_rate['tax']);
        }
        $this->assertTrue($testedTaxRates, 'tax_rates value remains untested. Make sure to at least provide one entry.');
    }

    /**
     * @return MerchantOrder
     */
    private function assembleMerchantOrder(): MerchantOrder
    {
        $order = $this->addDummyOrder(1);
        $resource = new MerchantOrderResource($order);

        $assembler = new MerchantOrderFactory($this->container('orderRepository'), $this->container);
        $merchantOrder = $assembler->create($resource);

        return $merchantOrder;
    }

    private function addDummyOrder($id)
    {
        $order = new DomainOrder(OrderId::fromInteger($id));
        $order->setCustomerId(CustomerId::fromString(22));
        $order->items()->add(DomainItem::fromPurchasable(new PurchasableStub(1, [], Cash::make(505), Percentage::fromPercent(10))));
        $order->items()->add(DomainItem::fromPurchasable(new PurchasableStub(2, [], Cash::make(1000), Percentage::fromPercent(10), Cash::make(800))), 2);
        $order->setShippingTotal(Cash::make(15));
        $order->setPaymentTotal(Cash::make(10));

        $discount = (new DiscountFactory(new InMemoryContainer()))->create(1, 'percentage_off', [], ['percentage' => Percentage::fromPercent(30)]);
        $discount->apply($order);

        $order->setTaxPercentage(Percentage::fromPercent(21));

        $this->container('orderRepository')->add($order);

        return $order;
    }
}
