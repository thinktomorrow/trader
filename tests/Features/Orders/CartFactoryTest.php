<?php

namespace Thinktomorrow\Trader\Tests\Features;

use Assert\Assertion;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\DiscountFactory;
use Thinktomorrow\Trader\Orders\Application\Reads\Cart\Cart;
use Thinktomorrow\Trader\Orders\Application\Reads\Cart\CartFactory;
use Thinktomorrow\Trader\Orders\Application\Reads\Cart\CartItem;
use Thinktomorrow\Trader\Orders\Domain\Item as DomainItem;
use Thinktomorrow\Trader\Orders\Domain\Order as DomainOrder;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Tests\InMemoryContainer;
use Thinktomorrow\Trader\Tests\Unit\Stubs\PurchasableStub;

class CartFactoryTest extends FeatureTestCase
{
    /** @test */
    public function it_can_resolve_cart()
    {
        $this->assertInstanceOf(Cart::class, $this->assembleCart());
    }

    /** @test */
    public function it_resolves_cart_with_expected_properties()
    {
        $merchantOrder = $this->assembleCart();

        $this->assertEquals('&euro;21.05', $merchantOrder->subtotal());
        $this->assertEquals('&euro;6.32', $merchantOrder->discountTotal());
        $this->assertEquals('&euro;0.15', $merchantOrder->shippingTotal());
        $this->assertEquals('&euro;0.10', $merchantOrder->paymentTotal());
        $this->assertEquals('&euro;14.98', $merchantOrder->total());

        $this->assertEquals('&euro;2.16', $merchantOrder->tax()); // TODO: discount is not taken into account yet.
    }

    /** @test */
    public function cart_contains_item_presenters()
    {
        $cart = $this->assembleCart();

        $this->assertCount(2, $cart->items());
        $this->assertTrue(Assertion::allIsInstanceOf($cart->items(), CartItem::class));
    }

    /**
     * @return Cart
     */
    private function assembleCart(): Cart
    {
        $this->addDummyOrder(1);

        $assembler = new CartFactory($this->container('orderRepository'), $this->container);
        return $assembler->create(1);
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
