<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingStateMachine;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderShipping;

class ShippingStateTest extends TestCase
{
    private ShippingStateMachine $machine;
    private \Thinktomorrow\Trader\Domain\Model\Order\Order $order;
    private \Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping $shipping;

    protected function setUp(): void
    {
        parent::setUp();

        $this->machine = new ShippingStateMachine([
            ShippingState::none,
            ShippingState::in_transit,
            ShippingState::delivered,
        ], [
            'ship' => [
                'from' => [ShippingState::none],
                'to' => ShippingState::in_transit,
            ],
            'deliver' => [
                'from' => [ShippingState::in_transit],
                'to' => ShippingState::delivered,
            ],
        ]);

        $this->order = $this->createDefaultOrder();
        $this->shipping = $this->order->getShippings()[0];

        $this->assertEquals(ShippingState::none, $this->shipping->getShippingState());

        $this->machine->setOrder($this->order);
    }

    public function test_it_can_create_state_machine()
    {
        $this->assertTrue($this->machine->can($this->shipping, 'ship'));
        $this->assertFalse($this->machine->can($this->shipping, 'deliver'));

        $this->machine->apply($this->shipping, 'ship');
        $this->assertEquals(ShippingState::in_transit, $this->shipping->getShippingState());

        $this->machine->apply($this->shipping, 'deliver');
        $this->assertEquals(ShippingState::delivered, $this->shipping->getShippingState());
    }

    public function test_it_can_create_state_machine_for_merchant_order()
    {
        $merchantOrderShipping = DefaultMerchantOrderShipping::fromMappedData(array_merge($this->shipping->getMappedData(), [
            'cost' => $this->shipping->getShippingCost(),
        ]), $this->order->getMappedData(), []);

        $this->assertTrue($this->machine->can($merchantOrderShipping, 'ship'));
        $this->assertFalse($this->machine->can($merchantOrderShipping, 'deliver'));

        $this->machine->apply($this->shipping, 'ship');
        $this->assertEquals(ShippingState::in_transit, $this->shipping->getShippingState());
    }

    public function test_it_can_create_machine_with_default_transitions()
    {
        $machine = new ShippingStateMachine(ShippingState::cases(), ShippingState::getDefaultTransitions());
        $machine->setOrder($this->order);

        $machine->apply($this->shipping, 'start_packing');
        $this->assertEquals(ShippingState::ready_for_packing, $this->shipping->getShippingState());
    }
}
