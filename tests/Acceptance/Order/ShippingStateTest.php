<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order;

use Thinktomorrow\Trader\Domain\Model\Order\Shipping\DefaultShippingState;

final class ShippingStateTest extends StateContext
{
    public function test_it_can_start_packing_a_shipment()
    {
        $this->assertShippingStateTransition('startPackingShipment', DefaultShippingState::none, DefaultShippingState::ready_for_packing);
    }

    public function test_it_can_halt_packing_a_shipment()
    {
        $this->assertShippingStateTransition('haltPackingShipment', DefaultShippingState::none, DefaultShippingState::halted_for_packing);
        $this->assertShippingStateTransition('haltPackingShipment', DefaultShippingState::ready_for_packing, DefaultShippingState::halted_for_packing);
    }

    public function test_it_can_pack_a_shipment()
    {
        $this->assertShippingStateTransition('packShipment', DefaultShippingState::ready_for_packing, DefaultShippingState::packed);
    }

    public function test_it_can_ship_a_shipment()
    {
        $this->assertShippingStateTransition('shipShipment', DefaultShippingState::packed, DefaultShippingState::in_transit);
    }

    public function test_it_can_deliver_a_shipment()
    {
        $this->assertShippingStateTransition('deliverShipment', DefaultShippingState::in_transit, DefaultShippingState::delivered);
    }

    public function test_it_can_return_a_shipment()
    {
        $this->assertShippingStateTransition('returnShipment', DefaultShippingState::in_transit, DefaultShippingState::returned);
        $this->assertShippingStateTransition('returnShipment', DefaultShippingState::delivered, DefaultShippingState::returned);
    }
}
