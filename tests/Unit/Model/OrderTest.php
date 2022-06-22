<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineDeleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderCreated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LinePrice;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderState;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;

class OrderTest extends TestCase
{
    /** @test */
    public function it_can_create_an_order_entity()
    {
        $order = Order::create(
            $orderId = OrderId::fromString('xxx'),
        );

        $this->assertEquals([
            'order_id' => $orderId->get(),
            'order_state' => OrderState::cart_pending->value,
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

    /** @test */
    public function it_can_update_shopper()
    {
        $order = $this->createdOrder();

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

    /** @test */
    public function it_can_add_shipping()
    {
        $order = $this->createdOrder();

        $order->addShipping(Shipping::create(
            $order->orderId, // TODO: avoid this here or assert it is the same...
            $shippingId = ShippingId::fromString('qqqq'),
            ShippingProfileId::fromString('postnl_home'),
            ShippingCost::fromScalars('23', '1', false)
        ));

        $this->assertCount(2, $order->getShippings());
        $this->assertEquals($shippingId, $order->getShippings()[1]->shippingId);

        $this->assertEquals([
            new ShippingAdded($order->orderId, $shippingId),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_update_shipping()
    {
        $order = $this->createdOrder();

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

    /** @test */
    public function it_can_update_payment()
    {
        $order = $this->createdOrder();

        $order->updatePayment(Payment::create(
            $order->orderId,
            $paymentId = PaymentId::fromString('ppp'),
            $paymentMethodId = PaymentMethodId::fromString('uuu'),
            PaymentCost::zero()
        ));

        $this->assertEquals($paymentId, $order->getPayment()->paymentId);
        $this->assertEquals($paymentMethodId, $order->getPayment()->getPaymentMethodId());

        $this->assertEquals([
            new OrderUpdated($order->orderId),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_update_shipping_address()
    {
        $order = $this->createdOrder();

        $addressPayload = [
            'address_id' => 'abc',
            'country' => 'NL',
            'line_1' => 'example 12',
            'line_2' => 'bus 2',
            'postal_code' => '1000',
            'city' => 'Amsterdam',
            'data' => "[]",
        ];

        $order->updateShippingAddress(ShippingAddress::fromMappedData($addressPayload, $order->getMappedData()));

        $this->assertEquals(ShippingAddress::fromMappedData($addressPayload, $order->getMappedData())->getMappedData(), $order->getChildEntities()[ShippingAddress::class]);
    }

    /** @test */
    public function it_can_update_billing_address()
    {
        $order = $this->createdOrder();

        $addressPayload = [
            'address_id' => 'def',
            'country' => 'FR',
            'line_1' => 'rue de napoleon 222',
            'line_2' => 'bus 999',
            'postal_code' => '3000',
            'city' => 'Paris',
            'data' => "[]",
        ];

        $order->updateBillingAddress(BillingAddress::fromMappedData($addressPayload, $order->getMappedData()));

        $this->assertEquals(BillingAddress::fromMappedData($addressPayload, $order->getMappedData())->getMappedData(), $order->getChildEntities()[BillingAddress::class]);
    }

    /** @test */
    public function it_can_add_a_line()
    {
        $order = $this->createdOrder();

        $order->addOrUpdateLine(
            LineId::fromString('abcdef'),
            VariantId::fromString('xxx'),
            $linePrice = LinePrice::fromScalars('250', '9', true),
            Quantity::fromInt(2),
            ['foo' => 'bar']
        );

        $this->assertCount(2, $order->getChildEntities()[Line::class]);

        $this->assertEquals([
            new LineAdded(
                $order->orderId,
                LineId::fromString('abcdef'),
                VariantId::fromString('xxx')
            ),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_update_a_line()
    {
        $order = $this->createdOrder();

        $order->addOrUpdateLine(
            LineId::fromString('abc'),
            VariantId::fromString('yyy'),
            $linePrice = LinePrice::fromScalars('200', '10', true),
            Quantity::fromInt(3),
            ['foo' => 'bar']
        );

        $firstLine = $order->getChildEntities()[Line::class][0];

        $this->assertCount(1, $order->getChildEntities()[Line::class]);
        $this->assertEquals('yyy', $firstLine['variant_id']);
        $this->assertEquals($linePrice->getMoney()->getAmount(), $firstLine['line_price']);
        $this->assertEquals(3, $firstLine['quantity']);
        $this->assertEquals(json_encode(['foo' => 'bar']), $firstLine['data']);

        $this->assertEquals([
            new LineUpdated(
                $order->orderId,
                LineId::fromString('abc'),
            ),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_delete_a_line()
    {
        $order = $this->createdOrder();

        $this->assertCount(1, $order->getChildEntities()[Line::class]);

        $order->deleteLine(
            LineId::fromString('abc'),
        );

        $this->assertCount(0, $order->getChildEntities()[Line::class]);

        $this->assertEquals([
            new LineDeleted(
                $order->orderId,
                LineId::fromString('abc'),
                VariantId::fromString('yyy'),
            ),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_add_a_discount()
    {
        $order = $this->createdOrder();

        $order->addDiscount(
            Discount::fromMappedData([
                'discount_id' => 'ababab',
                'total' => '32',
                'tax_rate' => '9',
                'includes_vat' => true,
                'data' => json_encode(['foo' => 'bar']),
            ], [
                'order_id' => $order->orderId->get(),
            ])
        );

        $this->assertCount(2, $order->getChildEntities()[Discount::class]);
    }

    /** @test */
    public function it_can_delete_a_discount()
    {
        $order = $this->createdOrder();

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);

        $order->deleteDiscount(
            DiscountId::fromString('ddd'),
        );

        $this->assertCount(0, $order->getChildEntities()[Discount::class]);
    }

    /** @test */
    public function adding_data_merges_with_existing_data()
    {
        $order = $this->createdOrder();

        $order->addData(['bar' => 'baz']);
        $order->addData(['foo' => 'bar', 'bar' => 'boo']);

        $this->assertEquals(json_encode(['bar' => 'boo', 'foo' => 'bar']), $order->getMappedData()['data']);
    }

    /** @test */
    public function it_can_delete_data()
    {
        $order = $this->createdOrder();

        $order->addData(['foo' => 'bar', 'bar' => 'boo']);
        $order->deleteData('bar');

        $this->assertEquals(json_encode(['foo' => 'bar']), $order->getMappedData()['data']);
    }
}
