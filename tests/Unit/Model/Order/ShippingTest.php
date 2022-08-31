<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
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
        $shipping = $this->createdShipping();

        $this->assertEquals(ShippingId::fromString('yyy'), $shipping->shippingId);
        $this->assertEquals([
            'order_id' => 'aaa',
            'shipping_id' => 'yyy',
            'shipping_profile_id' => 'zzz',
            'shipping_state' => ShippingState::in_transit->value,
            'cost' => '200',
            'tax_rate' => '9',
            'includes_vat' => true,
            'data' => json_encode([]),
        ], $shipping->getMappedData());
    }

    private function createdShipping(): Shipping
    {
        return Shipping::fromMappedData([
            'shipping_id' => 'yyy',
            'shipping_profile_id' => 'zzz',
            'shipping_state' => ShippingState::in_transit->value,
            'cost' => '200',
            'tax_rate' => '9',
            'includes_vat' => true,
            'data' => json_encode([]),
        ], [
            'order_id' => 'aaa',
        ], [
            Discount::class => [],
        ]);
    }
}
