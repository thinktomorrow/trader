<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order;

use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;

final class ShippingStateTest extends StateContext
{
    public function test_it_can_start_packing_a_shipment()
    {
        $this->assertShippingStateTransition('startPackingShipment', ShippingState::none, ShippingState::ready_for_packing);
    }

    public function test_it_can_halt_packing_a_shipment()
    {
        $this->assertShippingStateTransition('haltPackingShipment', ShippingState::none, ShippingState::halted_for_packing);
        $this->assertShippingStateTransition('haltPackingShipment', ShippingState::ready_for_packing, ShippingState::halted_for_packing);
    }

    public function test_it_can_pack_a_shipment()
    {
        $this->assertShippingStateTransition('packShipment', ShippingState::ready_for_packing, ShippingState::packed);
    }

    public function test_it_can_ship_a_shipment()
    {
        $this->assertShippingStateTransition('shipShipment', ShippingState::packed, ShippingState::in_transit);
    }

    public function test_it_can_deliver_a_shipment()
    {
        $this->assertShippingStateTransition('deliverShipment', ShippingState::in_transit, ShippingState::delivered);
    }

    public function test_it_can_return_a_shipment()
    {
        $this->assertShippingStateTransition('returnShipment', ShippingState::in_transit, ShippingState::returned);
        $this->assertShippingStateTransition('returnShipment', ShippingState::delivered, ShippingState::returned);
    }
}
