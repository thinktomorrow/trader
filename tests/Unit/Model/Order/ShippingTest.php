<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Money\Money;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingDeleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindShippingOnOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\ShippingAlreadyOnOrder;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;

class ShippingTest extends TestCase
{
    /** @test */
    public function it_can_create_a_order_shipping()
    {
        $shipping = Shipping::create(
            OrderId::fromString('aaa'),
            $shippingId = ShippingId::fromString('yyy'),
            $shippingProfileId = ShippingProfileId::fromString('zzz'),
            $cost = ShippingCost::fromScalars('150', '10', true),
        );

        $this->assertEquals([
            'order_id' => 'aaa',
            'shipping_id' => $shippingId->get(),
            'shipping_profile_id' => $shippingProfileId->get(),
            'shipping_state' => ShippingState::none->value,
            'cost' => $cost->getMoney()->getAmount(),
            'tax_rate' => $cost->getTaxRate()->toPercentage()->get(),
            'includes_vat' => $cost->includesVat(),
            'data' => json_encode([]),
        ], $shipping->getMappedData());
    }

    /** @test */
    public function it_can_be_build_from_raw_data()
    {
        $shipping = $this->createOrderShipping();

        $this->assertEquals(ShippingId::fromString('sss'), $shipping->shippingId);
        $this->assertEquals([
            'order_id' => 'xxx',
            'shipping_id' => 'sss',
            'shipping_profile_id' => 'ppp',
            'shipping_state' => ShippingState::none->value,
            'cost' => '30',
            'tax_rate' => '10',
            'includes_vat' => true,
            'data' => json_encode([]),
        ], $shipping->getMappedData());
    }

    /** @test */
    public function it_can_add_a_discount_to_shipping()
    {
        $order = $this->createDefaultOrder();
        $shipping = $order->getShippings()[0];
        $shipping->addDiscount($this->createOrderShippingDiscount(['promo_discount_id' => 'qqq', 'discount_id' => 'defgh'], $order->getMappedData()));

        $this->assertCount(1, $shipping->getDiscounts());

        $shippingCost = $shipping->getShippingCost();
        $this->assertEquals(ShippingCost::fromMoney(Money::EUR(0), $shippingCost->getTaxRate(), $shippingCost->includesVat()), $shipping->getShippingCostTotal());

        $this->assertEquals([
            Discount::class => array_map(fn ($discount) => $discount->getMappedData(), $shipping->getDiscounts()),
        ], $shipping->getChildEntities());
    }

    public function test_it_sets_discount_tax_the_same_as_discountable_tax()
    {
        $order = $this->createDefaultOrder();
        $shipping = $order->getShippings()[0];
        $shipping->addDiscount($this->createOrderShippingDiscount(['promo_discount_id' => 'qqq', 'discount_id' => 'defgh'], $order->getMappedData()));

        $discountTotal = DiscountTotal::fromMoney(Money::EUR('30'), $shipping->getShippingCost()->getTaxRate(), $shipping->getShippingCost()->includesVat());

        $this->assertEquals($discountTotal, $shipping->getDiscountTotal());
    }

    public function test_it_can_find_a_shipping()
    {
        $order = $this->createDefaultOrder();

        $shipping = $order->findShipping($order->getShippings()[0]->shippingId);

        $this->assertEquals($shipping, $order->getShippings()[0]);
    }

    public function test_it_fails_when_shipping_cannot_be_found()
    {
        $this->expectException(CouldNotFindShippingOnOrder::class);

        $order = $this->createDefaultOrder();
        $order->findShipping(ShippingId::fromString('unknown'));
    }

    public function test_it_fails_when_shipping_cannot_be_found_for_update()
    {
        $this->expectException(CouldNotFindShippingOnOrder::class);

        $order = $this->createDefaultOrder();
        $order->updateShipping($this->createOrderShipping(['shipping_id' => 'unknown']));
    }

    public function test_it_can_add_a_shipping()
    {
        $order = $this->createDefaultOrder();

        $order->addShipping($addedShipping = $this->createOrderShipping(['shipping_id' => 'hhhh']));

        $this->assertCount(2, $order->getShippings());

        $this->assertEquals([
            new ShippingAdded($order->orderId, $addedShipping->shippingId),
        ], $order->releaseEvents());
    }

    public function test_it_cannot_add_same_shipping()
    {
        $this->expectException(ShippingAlreadyOnOrder::class);

        $order = $this->createDefaultOrder();
        $order->addShipping($this->createOrderShipping());
    }

    /** @test */
    public function it_can_update_shipping()
    {
        $order = $this->createDefaultOrder();

        $shipping = $order->getShippings()[0];
        $shipping->updateCost($cost = ShippingCost::fromScalars('23', '1', false));

        $order->updateShipping($shipping);

        $this->assertCount(1, $order->getShippings());
        $this->assertEquals($cost, $order->getShippings()[0]->getShippingCost());

        $this->assertEquals([
            new ShippingUpdated($order->orderId, $shipping->shippingId),
        ], $order->releaseEvents());
    }

    public function test_it_can_delete_a_shipping()
    {
        $order = $this->createDefaultOrder();
        $shippingId = $order->getShippings()[0]->shippingId;

        $this->assertCount(1, $order->getShippings());

        $order->deleteShipping($shippingId);

        $this->assertCount(0, $order->getShippings());

        $this->assertEquals([
            new ShippingDeleted($order->orderId, $shippingId),
        ], $order->releaseEvents());
    }

    public function test_it_can_update_shipping_profile()
    {
        $shipping = $this->createOrderShipping();

        $shipping->updateShippingProfile($shippingProfileId = ShippingProfileId::fromString('eee'));
        $this->assertEquals($shippingProfileId, $shipping->getShippingProfileId());
    }
}
