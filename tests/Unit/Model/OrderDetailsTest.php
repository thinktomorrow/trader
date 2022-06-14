<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Price\Total;
use Thinktomorrow\Trader\Domain\Common\Address\AddressType;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;

class OrderDetailsTest extends TestCase
{
    /** @test */
    public function it_can_get_prices()
    {
        $order = $this->createdOrder();

        $this->assertEquals(
            Total::zero()->add(VariantSalePrice::fromScalars(400, 'EUR', '10', true)),
            $order->getSubTotal()
        );

        $this->assertEquals(
            Total::zero()->add(VariantSalePrice::fromScalars(440, 'EUR', '10', true)),
            $order->getTotal()
        );

        $this->assertEquals(
            ShippingCost::fromScalars(30, 'EUR', '10', true),
            $order->getShippingCost()
        );

        $this->assertEquals(
            PaymentCost::fromScalars(20, 'EUR', '10', true),
            $order->getPaymentCost()
        );

        $this->assertEquals(
            DiscountTotal::fromScalars(10, 'EUR', '10', true),
            $order->getDiscountTotal()
        );
    }

    /** @test */
    public function it_can_get_shipping_details()
    {
        $order = $this->createdOrder();

        $this->assertCount(1, $order->getShippings());
        $this->assertEquals(ShippingState::initialized, $order->getShippings()[0]->getShippingState());

        $this->assertEquals([
            'order_id' => 'xxx',
            'country' => 'BE',
            'line_1' => 'Lierseweg 81',
            'line_2' => null,
            'postal_code' => '2200',
            'city' => 'Herentals',
            'data' => "[]",
            'type' => AddressType::shipping->value,
        ], $order->getShippingAddress()->getMappedData());
    }

    /** @test */
    public function it_can_get_payment_details()
    {
        $order = $this->createdOrder();

        $this->assertEquals([
            'order_id' => 'xxx',
            'country' => 'NL',
            'line_1' => 'example 12',
            'line_2' => 'bus 2',
            'postal_code' => '1000',
            'city' => 'Amsterdam',
            'data' => "[]",
            'type' => AddressType::billing->value,
        ], $order->getBillingAddress()->getMappedData());
    }
}
