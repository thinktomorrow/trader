<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Money\Money;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustOrderVatSnapshot;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultServicePrice;
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
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

class OrderTest extends TestCase
{
    public function test_it_can_create_an_order_entity()
    {
        $order = Order::create(
            $orderId = OrderId::fromString('xxx'),
            $orderReference = OrderReference::fromString('xx-ref'),
            DefaultOrderState::cart_pending
        );

        (new TestContainer())->get(AdjustOrderVatSnapshot::class)->adjust($order);

        $this->assertEquals([
            'order_id' => 'xxx',
            'order_ref' => 'xx-ref',
            'invoice_ref' => null,
            'order_state' => 'cart_pending',
            'total_excl' => '0',
            'total_incl' => '0',
            'total_vat' => '0',
            'vat_lines' => '[]',
            'subtotal_excl' => '0',
            'subtotal_incl' => '0',
            'discount_excl' => '0',
            'discount_incl' => '0',
            'shipping_cost_excl' => '0',
            'shipping_cost_incl' => '0',
            'payment_cost_excl' => '0',
            'payment_cost_incl' => '0',
            'data' => '[]',
        ], $order->getMappedData());

        $this->assertEquals([
            new OrderCreated($orderId),
        ], $order->releaseEvents());
    }

    public function test_it_can_update_shopper()
    {
        $order = $this->orderContext->createDefaultOrder();

        $shopper = $order->getShopper();
        $shopper->updateCustomerId(CustomerId::fromString('zzz'));
        $order->updateShopper($shopper);

        $this->assertEquals(CustomerId::fromString('zzz'), $order->getShopper()->getCustomerId());
        $this->assertEquals(Email::fromString('ben@thinktomorrow.be'), $order->getShopper()->getEmail());
        $this->assertFalse($order->getShopper()->isBusiness());

        $this->assertEquals([
            new OrderUpdated($order->orderId),
        ], $order->releaseEvents());
    }

    public function test_it_can_add_shipping()
    {
        $order = $this->orderContext->createDefaultOrder();

        $order->addShipping(Shipping::create(
            $order->orderId, // TODO: avoid this here or assert it is the same...
            $shippingId = ShippingId::fromString('qqqq'),
            ShippingProfileId::fromString('postnl_home'),
            DefaultShippingState::getDefaultState(),
            DefaultServicePrice::fromExcludingVat(Money::EUR('23'))
        ));

        $this->assertCount(2, $order->getShippings());
        $this->assertEquals($shippingId, $order->getShippings()[1]->shippingId);

        $this->assertEquals([
            new ShippingAdded($order->orderId, $shippingId),
        ], $order->releaseEvents());
    }

    public function test_it_can_update_shipping()
    {
        $order = $this->orderContext->createDefaultOrder();

        /** @var \Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping $shipping */
        $shipping = $order->getShippings()[0];
        $shipping->updateCost($cost = DefaultServicePrice::fromExcludingVat(Money::EUR('23')));

        $order->updateShipping($shipping);

        $this->assertCount(1, $order->getShippings());
        $this->assertEquals($cost, $order->getShippings()[0]->getShippingCost());

        $this->assertEquals([
            new ShippingUpdated($order->orderId, $shipping->shippingId),
        ], $order->releaseEvents());
    }

    public function test_it_can_update_shipping_address()
    {
        $order = $this->orderContext->createDefaultOrder();

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
        $order = $this->orderContext->createDefaultOrder();

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
        $order = $this->orderContext->createDefaultOrder();

        $order->setInvoiceReference(
            $invoiceReference = InvoiceReference::fromString('invoice-ref')
        );

        $this->assertEquals($invoiceReference, $order->getInvoiceReference());
    }

    public function test_adding_data_merges_with_existing_data()
    {
        $order = $this->orderContext->createDefaultOrder();

        $order->addData(['bar' => 'baz']);
        $order->addData(['foo' => 'bar', 'bar' => 'boo']);

        $this->assertEquals(json_encode(['bar' => 'boo', 'foo' => 'bar']), $order->getMappedData()['data']);
    }

    public function test_it_can_delete_data()
    {
        $order = $this->orderContext->createDefaultOrder();

        $order->addData(['foo' => 'bar', 'bar' => 'boo']);
        $order->deleteData('bar');

        $this->assertEquals(json_encode(['foo' => 'bar']), $order->getMappedData()['data']);
    }

    public function test_it_can_add_log_entry()
    {
        $order = $this->orderContext->createDefaultOrder();

        $order->addLogEntry($logEntry = OrderEvent::create(OrderEventId::fromString('abc'), 'xxx', new \DateTime(), []));

        $this->assertCount(1, $order->getOrderEvents());
        $this->assertEquals($logEntry->getMappedData(), $order->getChildEntities()[OrderEvent::class][0]);
    }

    public function test_it_can_set_vat_exempt(): void
    {
        $order = $this->orderContext->createDefaultOrder();

        $order->setVatExempt(true);

        $this->assertTrue($order->isVatExempt());

        $this->assertTrue(json_decode($order->getMappedData()['data'], true)['is_vat_exempt']);

        $order->setVatExempt(false);

        $this->assertFalse($order->isVatExempt());
        $this->assertFalse(json_decode($order->getMappedData()['data'], true)['is_vat_exempt']);
    }
}
