<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Shopper;
use Thinktomorrow\Trader\Domain\Model\Order\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\LinePrice;
use Thinktomorrow\Trader\Domain\Model\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\LineNumber;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Payment\PaymentId;
use Thinktomorrow\Trader\Domain\Model\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineAdded;
use Thinktomorrow\Trader\Domain\Model\Payment\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineDeleted;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderCreated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingUpdated;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
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
            'data' => [],
        ], $order->getMappedData());

        $this->assertEquals([
            new OrderCreated($orderId),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_update_shopper()
    {
        $order = $this->createdOrder();

        $shopper = Shopper::create();
        $shopper->updateCustomerId(CustomerId::fromString('zzz'));
        $order->updateShopper($shopper);

        $this->assertEquals(CustomerId::fromString('zzz'), $order->getShopper()->getCustomerId());

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
            ShippingCost::fromScalars('23','EUR','1',false)
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

        /** @var Shipping $shipping */
        $shipping = $order->getShippings()[0];
        $shipping->updateCost($cost = ShippingCost::fromScalars('23','EUR','1',false));

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
            $paymentMethodId = PaymentMethodId::fromString('uuu'),
            PaymentCost::zero()
        ));

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
            'country' => 'NL',
            'street' => 'example',
            'number' => '12',
            'bus' => 'bus 2',
            'zipcode' => '1000',
            'city' => 'Amsterdam',
        ];

        $order->updateShippingAddress(ShippingAddress::fromArray($addressPayload));

        $this->assertEquals(ShippingAddress::fromArray($addressPayload)->toArray(), $order->getChildEntities()[ShippingAddress::class]);
    }

    /** @test */
    public function it_can_update_billing_address()
    {
        $order = $this->createdOrder();

        $addressPayload = [
            'country' => 'FR',
            'street' => 'rue de napoleon',
            'number' => '222',
            'bus' => 'bus 999',
            'zipcode' => '3000',
            'city' => 'Paris',
        ];

        $order->updateBillingAddress(BillingAddress::fromArray($addressPayload));

        $this->assertEquals(BillingAddress::fromArray($addressPayload)->toArray(), $order->getChildEntities()[BillingAddress::class]);
    }

    /** @test */
    public function it_can_add_a_line()
    {
        $order = $this->createdOrder();

        $order->addOrUpdateLine(
            LineNumber::fromInt(2),
            ProductId::fromString('xxx'),
            $linePrice = LinePrice::fromScalars('250', 'EUR', '9', true),
            Quantity::fromInt(2),
        );

        $this->assertCount(2, $order->getChildEntities()[Line::class]);

        $this->assertEquals([
            new LineAdded(
                $order->orderId,
                LineNumber::fromInt(2),
                ProductId::fromString('xxx')
            ),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_update_a_line()
    {
        $order = $this->createdOrder();

        $order->addOrUpdateLine(
            LineNumber::fromInt(1),
            ProductId::fromString('yyy'),
            $linePrice = LinePrice::fromScalars('200','EUR','10', true),
            Quantity::fromInt(3),
        );

        $firstLine = $order->getChildEntities()[Line::class][0];

        $this->assertCount(1, $order->getChildEntities()[Line::class]);
        $this->assertEquals('yyy', $firstLine['product_id']);
        $this->assertEquals($linePrice->getMoney()->getAmount(), $firstLine['line_price']);
        $this->assertEquals(3, $firstLine['quantity']);

        $this->assertEquals([
            new LineUpdated(
                $order->orderId,
                LineNumber::fromInt(1),
                ProductId::fromString('yyy'),
            ),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_delete_a_line()
    {
        $order = $this->createdOrder();

        $this->assertCount(1, $order->getChildEntities()[Line::class]);

        $order->deleteLine(
            LineNumber::fromInt(1),
        );

        $this->assertCount(0, $order->getChildEntities()[Line::class]);

        $this->assertEquals([
            new LineDeleted(
                $order->orderId,
                LineNumber::fromInt(1),
                ProductId::fromString('xxx'),
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

        $this->assertEquals(['bar' => 'boo', 'foo' => 'bar'], $order->getMappedData()['data']);
    }

    /** @test */
    public function it_can_delete_data()
    {
        $order = $this->createdOrder();

        $order->addData(['foo' => 'bar', 'bar' => 'boo']);
        $order->deleteData('bar');

        $this->assertEquals(['foo' => 'bar'], $order->getMappedData()['data']);
    }
}
