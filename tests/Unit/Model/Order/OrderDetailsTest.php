<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Address\AddressType;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\OrderTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\DefaultShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;

class OrderDetailsTest extends TestCase
{
    public function test_it_can_get_prices()
    {
        $order = $this->orderContext->createDefaultOrder();

        $this->assertEquals(
            OrderTotal::zero()->add(VariantSalePrice::fromScalars(200, '21', true)),
            $order->getSubTotal()
        );

        $this->assertEquals(
            ShippingCost::fromScalars(50, '21', true),
            $order->getShippingCost()
        );

        $this->assertEquals(
            PaymentCost::fromScalars(20, '21', true),
            $order->getPaymentCost()
        );

        $this->assertEquals(
            DiscountTotal::fromScalars(15, '21', true), // Default percentage
            $order->getDiscountTotal()
        );

        $this->assertEquals(
            OrderTotal::zero()
                ->add(VariantSalePrice::fromScalars(200, '21', true))
                ->add(ShippingCost::fromScalars(50, '21', true))
                ->add(PaymentCost::fromScalars(20, '21', true))
                ->subtract(DiscountTotal::fromScalars(15, '21', true)),
            $order->getTotal()
        );
    }

    public function test_it_can_get_shipping_details()
    {
        $order = $this->orderContext->createDefaultOrder();

        $this->assertCount(1, $order->getShippings());
        $this->assertEquals(DefaultShippingState::none, $order->getShippings()[0]->getShippingState());

        $this->assertEquals([
            'order_id' => 'order-aaa',
            'country_id' => 'BE',
            'line_1' => 'Lierseweg 81',
            'line_2' => '',
            'postal_code' => '2200',
            'city' => 'Herentals',
            'data' => "[]",
            'type' => AddressType::shipping->value,
        ], $order->getShippingAddress()->getMappedData());
    }

    public function test_it_can_get_payment_details()
    {
        $order = $this->orderContext->createDefaultOrder();

        $this->assertEquals([
            'order_id' => 'order-aaa',
            'country_id' => 'NL',
            'line_1' => 'Example 12',
            'line_2' => '',
            'postal_code' => '1000',
            'city' => 'Amsterdam',
            'data' => "[]",
            'type' => AddressType::billing->value,
        ], $order->getBillingAddress()->getMappedData());
    }
}
