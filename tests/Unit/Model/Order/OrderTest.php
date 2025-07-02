<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderCreated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceReference;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderEvent\OrderEvent;
use Thinktomorrow\Trader\Domain\Model\Order\OrderEvent\OrderEventId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\DefaultShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;

class OrderTest extends TestCase
{
    public function test_it_can_create_an_order_entity()
    {
        $order = Order::create(
            $orderId = OrderId::fromString('xxx'),
            $orderReference = OrderReference::fromString('xx-ref'),
            DefaultOrderState::cart_pending
        );

        $this->assertEquals([
            'order_id' => $orderId->get(),
            'order_ref' => $orderReference->get(),
            'order_state' => DefaultOrderState::cart_pending->value,
            'invoice_ref' => null,
            'total' => '0',
            'tax_total' => '0',
            'subtotal' => '0',
            'discount_total' => '0',
            'shipping_cost' => '0',
            'payment_cost' => '0',
            'includes_vat' => true,
            'data' => "[]",
        ], $order->getMappedData());

        $this->assertEquals([
            new OrderCreated($orderId),
        ], $order->releaseEvents());
    }

    public function test_it_can_update_shopper()
    {
        $order = $this->createDefaultOrder();

        $shopper = $order->getShopper();
        $shopper->updateCustomerId(CustomerId::fromString('zzz'));
        $order->updateShopper($shopper);

        $this->assertEquals(CustomerId::fromString('zzz'), $order->getShopper()->getCustomerId());
        $this->assertEquals(Email::fromString('ben@thinktomorrow.be'), $order->getShopper()->getEmail());
        $this->assertFalse($order->getShopper()->isBusiness());
        $this->assertTrue($order->getShopper()->registerAfterCheckout());

        $this->assertEquals([
            new OrderUpdated($order->orderId),
        ], $order->releaseEvents());
    }

    public function test_it_can_add_shipping()
    {
        $order = $this->createDefaultOrder();

        $order->addShipping(Shipping::create(
            $order->orderId, // TODO: avoid this here or assert it is the same...
            $shippingId = ShippingId::fromString('qqqq'),
            ShippingProfileId::fromString('postnl_home'),
            DefaultShippingState::getDefaultState(),
            ShippingCost::fromScalars('23', '1', false)
        ));

        $this->assertCount(2, $order->getShippings());
        $this->assertEquals($shippingId, $order->getShippings()[1]->shippingId);

        $this->assertEquals([
            new ShippingAdded($order->orderId, $shippingId),
        ], $order->releaseEvents());
    }

    public function test_it_can_update_shipping()
    {
        $order = $this->createDefaultOrder();

        /** @var \Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping $shipping */
        $shipping = $order->getShippings()[0];
        $shipping->updateCost($cost = ShippingCost::fromScalars('23', '1', false));

        $order->updateShipping($shipping);

        $this->assertCount(1, $order->getShippings());
        $this->assertEquals($cost, $order->getShippings()[0]->getShippingCost());

        $this->assertEquals([
            new ShippingUpdated($order->orderId, $shipping->shippingId),
        ], $order->releaseEvents());
    }

    public function test_it_can_update_shipping_address()
    {
        $order = $this->createDefaultOrder();

        $shippingAddress = ShippingAddress::fromMappedData([
            'address_id' => 'abc',
            'country_id' => 'NL',
            'line_1' => 'example 12',
            'line_2' => 'bus 2',
            'postal_code' => '1000',
            'city' => 'Amsterdam',
            'data' => json_encode(['foo' => 'bar']),
        ], $order->getMappedData());

        $order->updateShippingAddress($shippingAddress);

        $this->assertEquals($shippingAddress, $order->getShippingAddress());
        $this->assertEquals($shippingAddress->getAddress(), $order->getShippingAddress()->getAddress());
        $this->assertEquals($shippingAddress->getMappedData(), $order->getChildEntities()[ShippingAddress::class]);
    }

    public function test_it_can_update_billing_address()
    {
        $order = $this->createDefaultOrder();

        $billingAddress = BillingAddress::fromMappedData([
            'address_id' => 'def',
            'country_id' => 'FR',
            'line_1' => 'rue de napoleon 222',
            'line_2' => 'bus 999',
            'postal_code' => '3000',
            'city' => 'Paris',
            'data' => json_encode(['foo' => 'bar']),
        ], $order->getMappedData());

        $order->updateBillingAddress($billingAddress);

        $this->assertEquals($billingAddress, $order->getBillingAddress());
        $this->assertEquals($billingAddress->getAddress(), $order->getBillingAddress()->getAddress());
        $this->assertEquals($billingAddress->getMappedData(), $order->getChildEntities()[BillingAddress::class]);
    }

    public function test_it_can_set_invoice_ref()
    {
        $order = $this->createDefaultOrder();

        $order->setInvoiceReference(
            $invoiceReference = InvoiceReference::fromString('invoice-ref')
        );

        $this->assertEquals($invoiceReference, $order->getInvoiceReference());
    }

    public function test_adding_data_merges_with_existing_data()
    {
        $order = $this->createDefaultOrder();

        $order->addData(['bar' => 'baz']);
        $order->addData(['foo' => 'bar', 'bar' => 'boo']);

        $this->assertEquals(json_encode(['bar' => 'boo', 'foo' => 'bar']), $order->getMappedData()['data']);
    }

    public function test_it_can_delete_data()
    {
        $order = $this->createDefaultOrder();

        $order->addData(['foo' => 'bar', 'bar' => 'boo']);
        $order->deleteData('bar');

        $this->assertEquals(json_encode(['foo' => 'bar']), $order->getMappedData()['data']);
    }

    public function test_it_can_add_log_entry()
    {
        $order = $this->createDefaultOrder();

        $order->addLogEntry($logEntry = OrderEvent::create(OrderEventId::fromString('abc'), 'xxx', new \DateTime(), []));

        $this->assertCount(2, $order->getOrderEvents());
        $this->assertEquals($logEntry->getMappedData(), $order->getChildEntities()[OrderEvent::class][1]);
    }

    public function test_it_can_set_vat_exempt(): void
    {
        $order = $this->createDefaultOrder();

        $order->setVatExempt(true);

        $this->assertTrue($order->isVatExempt());

        $this->assertTrue(json_decode($order->getMappedData()['data'], true)['is_vat_exempt']);

        $order->setVatExempt(false);

        $this->assertFalse($order->isVatExempt());
        $this->assertFalse(json_decode($order->getMappedData()['data'], true)['is_vat_exempt']);
    }
}
