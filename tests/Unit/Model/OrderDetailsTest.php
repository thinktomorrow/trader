<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Address\AddressType;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\Order\Price\Total;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;

class OrderDetailsTest extends TestCase
{
    /** @test */
    public function it_can_get_prices()
    {
        $order = $this->createDefaultOrder();

        $this->assertEquals(
            Total::zero()->add(VariantSalePrice::fromScalars(400, '10', true)),
            $order->getSubTotal()
        );

        $this->assertEquals(
            ShippingCost::fromScalars(30, '10', true),
            $order->getShippingCost()
        );

        $this->assertEquals(
            PaymentCost::fromScalars(20, '10', true),
            $order->getPaymentCost()
        );

        $this->assertEquals(
            DiscountTotal::fromScalars(30, '21', true), // Default percentage
            $order->getDiscountTotal()
        );

        $this->assertEquals(
            Total::zero()
                ->add(VariantSalePrice::fromScalars(400, '10', true))
                ->add(ShippingCost::fromScalars(30, '10', true))
                ->add(PaymentCost::fromScalars(20, '10', true))
                ->subtract(DiscountTotal::fromScalars(30, '21', true)),
            $order->getTotal()
        );
    }

    /** @test */
    public function it_can_get_shipping_details()
    {
        $order = $this->createDefaultOrder();

        $this->assertCount(1, $order->getShippings());
        $this->assertEquals(ShippingState::none, $order->getShippings()[0]->getShippingState());

        $this->assertEquals([
            'order_id' => 'xxx',
            'country_id' => 'BE',
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
        $order = $this->createDefaultOrder();

        $this->assertEquals([
            'order_id' => 'xxx',
            'country_id' => 'NL',
            'line_1' => 'example 12',
            'line_2' => 'bus 2',
            'postal_code' => '1000',
            'city' => 'Amsterdam',
            'data' => "[]",
            'type' => AddressType::billing->value,
        ], $order->getBillingAddress()->getMappedData());
    }
}
