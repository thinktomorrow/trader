<?php
declare(strict_types=1);

namespace Tests\Unit;

use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\Shopper;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingAddress;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function createdOrder(): Order
    {
        return Order::fromMappedData([
            'order_id' => 'xxx',
            'data' => [],
        ], [
            Line::class => [
                [
                    'line_id' => 1,
                    'variant_id' => 'xxx',
                    'line_price' => 200,
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
            Discount::class => [
                [
                    'discount_id' => 'ddd',
                    'discount_type' => 'percentage_off',
                    'total' => '10',
                    'tax_rate' => '10',
                    'includes_vat' => true,
                ],
            ],
            Shipping::class => [
                [
                    'shipping_id' => 'sss',
                    'shipping_profile_id' => 'ppp',
                    'shipping_state' => ShippingState::initialized->value,
                    'shipping_cost' => '30',
                    'tax_rate' => '10',
                    'includes_vat' => true,
                    'data' => [],
                ]
            ],
            Payment::class => [
                'payment_method_id' => 'mmm',
                'payment_state' => PaymentState::initialized->value,
                'payment_cost' => '20',
                'tax_rate' => '10',
                'includes_vat' => true,
                'data' => [],
            ],
            Shopper::class => [
                'email' => 'ben@thinktomorrow.be',
                'firstname' => 'Ben',
                'lastname' => 'Cavens',
                'register_after_checkout' => true,
                'customer_id' => null,
            ],
        ]);
    }
}
