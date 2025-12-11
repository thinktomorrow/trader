<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Address\AddressType;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemDiscount;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultTotalPrice;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\DefaultShippingState;

class OrderDetailsTest extends TestCase
{
    public function test_it_can_get_prices()
    {
        $order = $this->createDefaultOrder();

        $this->assertEquals(
            DefaultTotalPrice::zero()->add(
                DefaultItemPrice::fromMoney(Cash::make(400), VatPercentage::fromString('10'), true)
            ),
            $order->getSubTotal()
        );

        $this->assertEquals(
            DefaultTotalPrice::zero()->add(
                DefaultItemPrice::fromScalars(30, '10', true)
            ),
            $order->getShippingCost()
        );

        $this->assertEquals(
            DefaultTotalPrice::zero()->add(
                DefaultItemPrice::fromScalars(20, '10', true)
            ),
            $order->getPaymentCost()
        );

        $this->assertEquals(
            DefaultItemDiscount::fromScalars(30, '9', true),
            $order->getDiscountTotal()
        );

        $this->assertEquals(
            DefaultTotalPrice::zero()
                ->add(DefaultItemPrice::fromScalars(400, '10', true))
                ->add(DefaultItemPrice::fromScalars(30, '10', true))
                ->add(DefaultItemPrice::fromScalars(20, '10', true))
                ->subtract(DefaultItemDiscount::fromScalars(30, '9', true)),
            $order->getTotal()
        );
    }

    public function test_it_can_get_shipping_details()
    {
        $order = $this->createDefaultOrder();

        $this->assertCount(1, $order->getShippings());
        $this->assertEquals(DefaultShippingState::none, $order->getShippings()[0]->getShippingState());

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

    public function test_it_can_get_payment_details()
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
