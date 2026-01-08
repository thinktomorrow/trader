<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Money\Money;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultServicePrice;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingDeleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindShippingOnOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\ShippingAlreadyOnOrder;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\DefaultShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;

class ShippingTest extends TestCase
{
    public function test_it_can_create_a_order_shipping()
    {
        $shipping = Shipping::create(
            OrderId::fromString('aaa'),
            $shippingId = ShippingId::fromString('yyy'),
            $shippingProfileId = ShippingProfileId::fromString('zzz'),
            $state = DefaultShippingState::getDefaultState(),
            $cost = DefaultServicePrice::fromExcludingVat(Money::EUR(150)),
        );

        $this->assertEquals([
            'order_id' => 'aaa',
            'shipping_id' => $shippingId->get(),
            'shipping_profile_id' => $shippingProfileId->get(),
            'shipping_state' => $state->value,
            'cost_excl' => $cost->getExcludingVat()->getAmount(),
            'discount_excl' => 0,
            'total_excl' => $cost->getExcludingVat()->getAmount(),
            'data' => json_encode(['shipping_profile_id' => $shippingProfileId->get()]),
        ], $shipping->getMappedData());
    }

    public function test_it_can_be_build_from_raw_data()
    {
        $shipping = $this->orderContext->createShipping();

        $this->assertEquals(ShippingId::fromString('order-aaa:shipping-aaa'), $shipping->shippingId);
        $this->assertEquals([
            'order_id' => 'order-aaa',
            'shipping_id' => 'order-aaa:shipping-aaa',
            'shipping_profile_id' => 'shipping-profile-aaa',
            'shipping_state' => DefaultShippingState::none->value,
            'cost_excl' => '50',
            'discount_excl' => '0',
            'total_excl' => '50',
            'data' => json_encode([
                'title' => ['nl' => 'shipping-aaa title nl', 'fr' => 'shipping-aaa title fr'],
                'shipping_profile_id' => 'shipping-profile-aaa'
            ]),
        ], $shipping->getMappedData());
    }

    public function test_it_can_update_shipping_profile()
    {
        $shipping = $this->orderContext->createShipping();

        $shipping->updateShippingProfile($shippingProfileId = ShippingProfileId::fromString('eee'));
        $this->assertEquals($shippingProfileId, $shipping->getShippingProfileId());
    }

    public function test_it_can_find_a_shipping()
    {
        $order = $this->orderContext->createDefaultOrder();

        $shipping = $order->findShipping($order->getShippings()[0]->shippingId);

        $this->assertEquals($shipping, $order->getShippings()[0]);
    }

    public function test_it_fails_when_shipping_cannot_be_found()
    {
        $this->expectException(CouldNotFindShippingOnOrder::class);

        $order = $this->orderContext->createOrder();
        $order->findShipping(ShippingId::fromString('unknown'));
    }

    public function test_it_fails_when_shipping_cannot_be_found_for_update()
    {
        $this->expectException(CouldNotFindShippingOnOrder::class);

        $order = $this->orderContext->createOrder();
        $order->updateShipping($this->orderContext->createShipping());
    }

    public function test_it_can_add_a_shipping()
    {
        $order = $this->orderContext->createOrder();

        $order->addShipping($addedShipping = $this->orderContext->createShipping());

        $this->assertCount(1, $order->getShippings());

        $this->assertEquals([
            new ShippingAdded($order->orderId, $addedShipping->shippingId),
        ], $order->releaseEvents());
    }

    public function test_it_cannot_add_same_shipping()
    {
        $this->expectException(ShippingAlreadyOnOrder::class);

        $order = $this->orderContext->createDefaultOrder();
        $order->addShipping($this->orderContext->createShipping());
    }

    public function test_it_can_update_shipping()
    {
        $order = $this->orderContext->createDefaultOrder();

        $shipping = $order->getShippings()[0];
        $shipping->updateCost($cost = DefaultServicePrice::fromExcludingVat(Money::EUR(20)));

        $order->updateShipping($shipping);

        $this->assertCount(1, $order->getShippings());
        $this->assertEquals($cost, $order->getShippings()[0]->getShippingCost());

        $this->assertEquals([
            new ShippingUpdated($order->orderId, $shipping->shippingId),
        ], $order->releaseEvents());
    }

    public function test_it_can_delete_a_shipping()
    {
        $order = $this->orderContext->createDefaultOrder();
        $shippingId = $order->getShippings()[0]->shippingId;

        $this->assertCount(1, $order->getShippings());

        $order->deleteShipping($shippingId);

        $this->assertCount(0, $order->getShippings());

        $this->assertEquals([
            new ShippingDeleted($order->orderId, $shippingId),
        ], $order->releaseEvents());
    }

    public function test_it_can_add_a_discount_to_shipping()
    {
        $order = $this->orderContext->createDefaultOrder();
        $shipping = $order->getShippings()[0];
        $shipping->addDiscount($this->orderContext->createShippingDiscount());

        $this->assertCount(1, $shipping->getDiscounts());

        $this->assertEquals(DefaultServicePrice::fromExcludingVat(Money::EUR(50)), $shipping->getShippingCost());
        $this->assertEquals(DefaultDiscountPrice::fromExcludingVat(Money::EUR(15)), $shipping->getSumOfDiscountPrices());
        $this->assertEquals(DefaultServicePrice::fromExcludingVat(Money::EUR(35)), $shipping->getShippingCostTotal());
    }
}
