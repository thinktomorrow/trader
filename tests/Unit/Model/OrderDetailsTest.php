<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Price\Total;
use Thinktomorrow\Trader\Domain\Model\Order\Price\SubTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;

class OrderDetailsTest extends TestCase
{
    /** @test */
    public function it_can_get_prices()
    {
        $order = $this->createdOrder();

        $this->assertEquals(
            SubTotal::fromScalars(400, 'EUR', '10', true),
            $order->getSubTotal()
        );

        $this->assertEquals(
            Total::fromScalars(440, 'EUR', '10', true),
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
            'country' => 'BE',
            'street' => 'Lierseweg',
            'number' => '81',
            'bus' => null,
            'zipcode' => '2200',
            'city' => 'Herentals',
        ], $order->getShippingAddress()->toArray());
    }

    /** @test */
    public function it_can_get_payment_details()
    {
        $order = $this->createdOrder();

        $this->assertEquals([
            'country' => 'NL',
            'street' => 'example',
            'number' => '12',
            'bus' => 'bus 2',
            'zipcode' => '1000',
            'city' => 'Amsterdam',
        ], $order->getBillingAddress()->toArray());
    }
}
