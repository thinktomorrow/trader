<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\LineNumber;
use Thinktomorrow\Trader\Domain\Model\Order\Entity\Line;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Payment\PaymentId;
use Thinktomorrow\Trader\Domain\Model\Order\Entity\Order;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineAdded;
use Thinktomorrow\Trader\Domain\Model\Payment\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineDeleted;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderCreated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderUpdated;

class OrderTest extends TestCase
{
    /** @test */
    public function it_can_create_an_order_entity()
    {
        $order = Order::create(
            $orderId = OrderId::fromString('xxx'),
            $customerId = CustomerId::fromString('yyy'),
        );

        $this->assertEquals([
            'order_id' => $orderId->get(),
            'customer_id' => $customerId->get(),
            'shipping_id' => null,
            'payment_id' => null,
        ], $order->getMappedData());

        $this->assertEquals([
            new OrderCreated($orderId),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_update_customer()
    {
        $order = $this->createdOrder();

        $order->updateCustomer(CustomerId::fromString('zzz'));

        $this->assertEquals(CustomerId::fromString('zzz'), $order->getMappedData()['customer_id']);

        $this->assertEquals([
            new OrderUpdated($order->orderId),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_update_shipping()
    {
        $order = $this->createdOrder();

        $order->updateShipping(ShippingId::fromString('zzz'));

        $this->assertEquals('zzz', $order->getMappedData()['shipping_id']);

        $this->assertEquals([
            new OrderUpdated($order->orderId),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_update_payment()
    {
        $order = $this->createdOrder();

        $order->updatePayment(PaymentId::fromString('zzz'));

        $this->assertEquals('zzz', $order->getMappedData()['payment_id']);

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

        $this->assertEquals(ShippingAddress::fromArray($addressPayload), $order->getChildEntities()[ShippingAddress::class]);
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

        $this->assertEquals(BillingAddress::fromArray($addressPayload), $order->getChildEntities()[BillingAddress::class]);
    }

    /** @test */
    public function it_can_be_build_from_raw_data()
    {
        $order = Order::fromMappedData([
            'order_id' => 'xxx',
            'customer_id' => 'yyy',
            'shipping_id' => 'bbb',
            'payment_id' => 'ccc',
        ]);

        $this->assertEquals(OrderId::fromString('xxx'), $order->orderId);
        $this->assertEquals('yyy', $order->getMappedData()['customer_id']);
        $this->assertEquals('bbb', $order->getMappedData()['shipping_id']);
        $this->assertEquals('ccc', $order->getMappedData()['payment_id']);
    }

    /** @test */
    public function it_can_add_a_line()
    {
        $order = $this->createdOrder();

        $order->addOrUpdateLine(
            LineNumber::fromInt(1),
            ProductId::fromString('xxx'),
            Quantity::fromInt(2),
        );

        $this->assertCount(1, $order->getChildEntities()[Line::class]);

        $this->assertEquals([
            new LineAdded(
                $order->orderId,
                LineNumber::fromInt(1),
                ProductId::fromString('xxx'),
                Quantity::fromInt(2),
            ),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_update_a_line()
    {
        $order = $this->createdOrder();

        $order->addOrUpdateLine(
            LineNumber::fromInt(1),
            ProductId::fromString('xxx'),
            Quantity::fromInt(2),
        );

        $order->addOrUpdateLine(
            LineNumber::fromInt(1),
            ProductId::fromString('yyy'),
            Quantity::fromInt(3),
        );

        $this->assertCount(1, $order->getChildEntities()[Line::class]);
        $this->assertEquals('yyy', $order->getChildEntities()[Line::class][0]->getProductId()->get());
        $this->assertEquals(3, $order->getChildEntities()[Line::class][0]->getQuantity()->asInt());

        $this->assertEquals([
            new LineAdded(
                $order->orderId,
                LineNumber::fromInt(1),
                ProductId::fromString('xxx'),
                Quantity::fromInt(2),
            ),
            new LineUpdated(
                $order->orderId,
                LineNumber::fromInt(1),
                ProductId::fromString('yyy'),
                Quantity::fromInt(3),
            ),
        ], $order->releaseEvents());
    }

    /** @test */
    public function it_can_delete_a_line()
    {
        $order = $this->createdOrder();

        $order->addOrUpdateLine(
            LineNumber::fromInt(1),
            ProductId::fromString('xxx'),
            Quantity::fromInt(2),
        );

        $this->assertCount(1, $order->getChildEntities()[Line::class]);

        $order->deleteLine(
            LineNumber::fromInt(1),
        );

        $this->assertCount(0, $order->getChildEntities()[Line::class]);

        $this->assertEquals([
            new LineAdded(
                $order->orderId,
                LineNumber::fromInt(1),
                ProductId::fromString('xxx'),
                Quantity::fromInt(2),
            ),
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
            DiscountId::fromString('aaa'),
        );

        $this->assertCount(1, $order->getChildEntities()[DiscountId::class]);
    }

    /** @test */
    public function it_can_delete_a_discount()
    {
        $order = $this->createdOrder();

        $order->addDiscount(
            DiscountId::fromString('aaa'),
        );

        $this->assertCount(1, $order->getChildEntities()[DiscountId::class]);

        $order->deleteDiscount(
            DiscountId::fromString('aaa'),
        );

        $this->assertCount(0, $order->getChildEntities()[DiscountId::class]);
    }

    private function createdOrder(): Order
    {
        return Order::fromMappedData([
            'order_id' => 'xxx',
            'customer_id' => 'yyy',
            'shipping_id' => 'aaa',
            'payment_id' => 'bbb',
        ], [
        \Thinktomorrow\Trader\Domain\Model\Order\Details\Line::class => [
            [
                'product_unit_price' => 200,
                'tax_rate' => '10',
                'includes_vat' => true,
                'quantity' => 2,
            ],
        ],
        ShippingAddress::class => [
            'country' => 'BE',
            'street' => 'Lierseweg',
            'number' => '81',
            'bus' => null,
            'zipcode' => '2200',
            'city' => 'Herentals',
        ],
        BillingAddress::class => [
            'country' => 'NL',
            'street' => 'example',
            'number' => '12',
            'bus' => 'bus 2',
            'zipcode' => '1000',
            'city' => 'Amsterdam',
        ],
        DiscountId::class => [
            'ddd',
            'eee',
        ],
    ]);
    }
}
