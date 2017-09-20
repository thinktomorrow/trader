<?php

namespace Thinktomorrow\Trader\Tests;

use Assert\Assertion;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\DiscountFactory;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Orders\Domain\Read\Cart;
use Thinktomorrow\Trader\Orders\Domain\Read\CartFactory;
use Thinktomorrow\Trader\Orders\Domain\Read\CartItem\CartItem;
use Thinktomorrow\Trader\Orders\Domain\CustomerId;
use Thinktomorrow\Trader\Orders\Domain\Item as DomainItem;
use Thinktomorrow\Trader\Orders\Domain\Order as DomainOrder;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;

class CartFactoryTest extends FeatureTestCase
{
    /** @test */
    public function it_can_resolve_cart()
    {
        $this->assertInstanceOf(Cart::class, $this->cart());
    }

    /** @test */
    function cart_displays_totals_as_localized_strings()
    {
        $cart = $this->cart($order = $this->purchase(1));

        $this->assertEquals($order->id()->get(), $cart->id());
        $this->assertEquals($order->reference(), $cart->reference());

        // Assert numbers
        $this->assertEquals(Cash::from($order->total())->locale(), $cart->total());
        $this->assertEquals(Cash::from($order->subtotal())->locale(), $cart->subtotal());
        $this->assertEquals(Cash::from($order->paymentTotal())->locale(), $cart->paymentTotal());
        $this->assertEquals(Cash::from($order->shippingTotal())->locale(), $cart->shippingTotal());
        $this->assertEquals(Cash::from($order->discountTotal())->locale(), $cart->discountTotal());
        $this->assertEquals(Cash::from($order->tax())->locale(), $cart->tax());
    }

    /** @test */
    public function cart_has_collection_of_cartitems()
    {
        $cart = $this->cart($order = $this->purchase(1));

        $this->assertCount(2, $cart->items());
        $this->assertEquals(2, $cart->size());
        $this->assertFalse($cart->empty());

        $this->assertTrue(Assertion::allIsInstanceOf($cart->items(), CartItem::class));
    }

    /** @test */
    public function cart_has_displayable_tax_rates_grouped_by_rate()
    {
        $cart = $this->cart($order = $this->purchase(1));

        $this->assertCount(2, $cart->taxRates());

        $testedTaxRates = false;
        foreach ($cart->taxRates() as $percent => $tax_rate) {
            $testedTaxRates = true;

            $orderTax = $order->taxRates()[$percent];
            $this->assertEquals($orderTax['percent']->asPercent(), $tax_rate['percent']);
            $this->assertEquals(Cash::from($orderTax['tax'])->locale(), $tax_rate['tax']);
            $this->assertEquals(Cash::from($orderTax['total'])->locale(), $tax_rate['total']);
        }
        $this->assertTrue($testedTaxRates, 'tax_rates value remains untested. Make sure to at least provide one entry.');
    }

    private function cart(Order $order = null): Cart
    {
        if(!$order) $order = $this->purchase(1);

        return (new CartFactory($this->container('orderRepository'), $this->container))->create($order);
    }
}
