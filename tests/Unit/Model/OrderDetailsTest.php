<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Price\Total;
use Thinktomorrow\Trader\Domain\Model\Order\Price\SubTotal;
use Thinktomorrow\Trader\Domain\Model\Payment\PaymentState;
use Thinktomorrow\Trader\Domain\Model\Payment\PaymentTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Details\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Details\Discount;
use Thinktomorrow\Trader\Domain\Model\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingTotal;
use Thinktomorrow\Trader\Domain\Model\Payment\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Details\Shipping;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Details\OrderDetails;

class OrderDetailsTest extends TestCase
{
    /** @test */
    public function it_can_get_prices()
    {
        $orderDetails = $this->orderDetails();


        $this->assertEquals(
            SubTotal::fromScalars(400, 'EUR', '10', true),
            $orderDetails->getSubTotal()
        );

        $this->assertEquals(
            Total::fromScalars(425, 'EUR', '10', true),
            $orderDetails->getTotal()
        );

        $this->assertEquals(
            ShippingTotal::fromScalars(20, 'EUR', '20', true),
            $orderDetails->getShippingTotal()
        );

        $this->assertEquals(
            PaymentTotal::fromScalars(20, 'EUR', '20', true),
            $orderDetails->getPaymentTotal()
        );

        $this->assertEquals(
            DiscountTotal::fromScalars(15, 'EUR', '10', true),
            $orderDetails->getDiscountTotal()
        );
    }

    /** @test */
    public function it_can_get_shipping_details()
    {
        $orderDetails = $this->orderDetails();

        $this->assertEquals(ShippingState::initialized, $orderDetails->getShippingState());

        $this->assertEquals([
            'country' => 'BE',
            'street' => 'Lierseweg',
            'number' => '81',
            'bus' => null,
            'zipcode' => '2200',
            'city' => 'Herentals',
        ], $orderDetails->getShippingAddress()->toArray());
    }

    /** @test */
    public function it_can_get_payment_details()
    {
        $orderDetails = $this->orderDetails();

        $this->assertEquals([
            'country' => 'NL',
            'street' => 'example',
            'number' => '12',
            'bus' => 'bus 2',
            'zipcode' => '1000',
            'city' => 'Amsterdam',
        ], $orderDetails->getBillingAddress()->toArray());
    }

    private function orderDetails(): OrderDetails
    {
        return OrderDetails::fromMappedData([
            'order_id' => 'xxx',
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
            Shipping::class => [
                'type' => '',
                'state' => ShippingState::initialized->value,
                'cost' => 20,
                'tax_rate' => '20',
                'includes_vat' => true,
            ],
            Payment::class => [
                'type' => '',
                'state' => PaymentState::initialized->value,
                'cost' => 20,
                'tax_rate' => '20',
                'includes_vat' => true,
            ],
            Discount::class => [
                [
                    'type' => 'percentage_off',
                    'total' => 15,
                    'tax_rate' => '10',
                    'includes_vat' => true,
                ],
            ],
        ]);
    }
}
