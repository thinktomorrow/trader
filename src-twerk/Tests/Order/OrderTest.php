<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Order;

use Thinktomorrow\Trader\Order\Domain\PaymentState;
use Thinktomorrow\Trader\Order\Domain\ShippingState;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Thinktomorrow\Trader\Common\Address\Address;
use Thinktomorrow\Trader\Common\Cash\Cash;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;
use Thinktomorrow\Trader\Order\Domain\OrderCustomer;
use Thinktomorrow\Trader\Order\Domain\OrderPayment;
use Thinktomorrow\Trader\Order\Domain\OrderProductCollection;
use Thinktomorrow\Trader\Order\Domain\OrderReference;
use Thinktomorrow\Trader\Order\Domain\OrderRepository;
use Thinktomorrow\Trader\Order\Domain\OrderShipping;
use Thinktomorrow\Trader\Taxes\TaxRate;

class OrderTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function by_default_it_will_return_a_new_empty_order()
    {
        $order = $this->storeOrder('xxx');

        $this->assertTrue($order->isEmpty());
        $this->assertEquals(0, $order->getSize());
        $this->assertEquals(app(OrderProductCollection::class), $order->getItems());

        $this->assertNotEquals(OrderShipping::empty()->getId(), $order->getShipping()->getId());
        $this->assertEquals(OrderShipping::empty()->getMethod(), $order->getShipping()->getMethod());
        $this->assertEquals(OrderShipping::empty()->getTotal(), $order->getShipping()->getTotal());

        $this->assertNotEquals(OrderPayment::empty()->getId(), $order->getPayment()->getId());
        $this->assertEquals(OrderPayment::empty()->getMethod(), $order->getPayment()->getMethod());
        $this->assertEquals(OrderPayment::empty()->getTotal(), $order->getPayment()->getTotal());

        $this->assertNotEquals(OrderCustomer::empty()->getId(), $order->getCustomer()->getId());
        $this->assertEquals(OrderCustomer::empty()->getCustomerId(), $order->getCustomer()->getCustomerId());
        $this->assertEquals(OrderCustomer::empty()->getEmail(), $order->getCustomer()->getEmail());
        $this->assertEquals(OrderCustomer::empty()->getShippingAddress(), $order->getCustomer()->getShippingAddress());
        $this->assertEquals(OrderCustomer::empty()->getBillingAddress(), $order->getCustomer()->getBillingAddress());
    }

    /** @test */
    public function it_can_store_order()
    {
        $order = $this->storeOrder('xxx');

        $storedOrder = app(OrderRepository::class)->findByReference(OrderReference::fromString('xxx'));

        $this->assertEquals($order, $storedOrder);
    }

    /** @test */
    public function it_can_retrieve_order_by_reference()
    {
        $order = $this->storeOrder('xxx');

        $storedOrder = app(OrderRepository::class)->findByReference(OrderReference::fromString('xxx'));

        $this->assertEquals($order, $storedOrder);
    }

    /** @test */
    public function it_can_add_product()
    {
        $order = $this->storeOrder('xxx');
        $order->getItems()->addItem($this->defaultOrderProduct());

        $this->assertEquals(1, $order->getSize());

        $storedOrder = $this->storeOrder('xxx', $order);
        $this->assertEquals(1, $storedOrder->getSize());
    }

    /** @test */
    public function it_can_replace_order_shipping_and_payment()
    {
        $order = $this->storeOrder('xxx');

        $order->replaceShipping($orderShipping = new OrderShipping(
            null,
            "xxx",
            ShippingState::fromString(ShippingState::UNKNOWN),
            Cash::make(100),
            TaxRate::fromInteger(21),
            new AppliedDiscountCollection(),
            Address::empty(),
            []
        ));

        $order->replacePayment($orderPayment = new OrderPayment(
            null,
            "xxx",
            \Thinktomorrow\Trader\Order\Domain\PaymentState::fromString(\Thinktomorrow\Trader\Order\Domain\PaymentState::UNKNOWN),
            Cash::make(100),
            TaxRate::fromInteger(21),
            new AppliedDiscountCollection(),
            []
        ));

        $storedOrder = $this->storeOrder($order->getReference()->get(), $order);

        // The services are stored so they now exist as db records and have obtained an ID.
        $this->assertTrue($storedOrder->getShipping()->exists());
        $this->assertEquals($orderShipping->getMethod(), $storedOrder->getShipping()->getMethod());
        $this->assertEquals($orderShipping->getShippingState(), $storedOrder->getShipping()->getShippingState());
        $this->assertEquals($orderShipping->getTotal(), $storedOrder->getShipping()->getTotal());
        $this->assertEquals($orderShipping->getTaxRate(), $storedOrder->getShipping()->getTaxRate());
        $this->assertEquals($orderShipping->getDiscounts(), $storedOrder->getShipping()->getDiscounts());
        $this->assertEquals($orderShipping->getAddress(), $storedOrder->getShipping()->getAddress());

        $this->assertTrue($storedOrder->getPayment()->exists());
        $this->assertEquals($orderPayment->getMethod(), $storedOrder->getPayment()->getMethod());
        $this->assertEquals($orderPayment->getPaymentState(), $storedOrder->getPayment()->getPaymentState());
        $this->assertEquals($orderPayment->getTotal(), $storedOrder->getPayment()->getTotal());
        $this->assertEquals($orderPayment->getTaxRate(), $storedOrder->getPayment()->getTaxRate());
        $this->assertEquals($orderPayment->getDiscounts(), $storedOrder->getPayment()->getDiscounts());
    }

    /** @test */
    public function it_can_replace_order_customer()
    {
        $order = $this->emptyOrder('xxx');

        $order->replaceCustomer($orderCustomer = new OrderCustomer(
            null,
            "1",
            "ben@tt.be",
            Address::empty()->replaceCountry('BE')->replaceZipcode('1234'),
            Address::empty()->replaceCountry('NL')->replaceZipcode('AB 1234'),
            []
        ));

        $storedOrder = $this->storeOrder($order->getReference()->get(), $order);

        // The services are stored so they now exist as db records and have obtained an ID.
        $this->assertTrue($storedOrder->getCustomer()->exists());
        $this->assertNotEquals($orderCustomer->getId(), $storedOrder->getCustomer()->getId());

        $this->assertEquals($orderCustomer->getCustomerId(), $storedOrder->getCustomer()->getCustomerId());
        $this->assertEquals($orderCustomer->getEmail(), $storedOrder->getCustomer()->getEmail());
        $this->assertEquals($orderCustomer->getBillingAddress(), $storedOrder->getCustomer()->getBillingAddress());
        $this->assertEquals($orderCustomer->getShippingAddress(), $storedOrder->getCustomer()->getShippingAddress());
    }
}
