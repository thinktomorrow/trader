<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\DefaultShippingState;
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
            DefaultShippingState::none,
            DefaultShippingState::in_transit,
            DefaultShippingState::delivered,
        ], [
            'ship' => [
                'from' => [DefaultShippingState::none],
                'to' => DefaultShippingState::in_transit,
            ],
            'deliver' => [
                'from' => [DefaultShippingState::in_transit],
                'to' => DefaultShippingState::delivered,
            ],
        ]);

        $this->order = $this->createDefaultOrder();
        $this->shipping = $this->order->getShippings()[0];

        $this->assertEquals(DefaultShippingState::none, $this->shipping->getShippingState());

        $this->machine->setOrder($this->order);
    }

    public function test_it_can_create_state_machine()
    {
        $this->assertTrue($this->machine->can($this->shipping, 'ship'));
        $this->assertFalse($this->machine->can($this->shipping, 'deliver'));

        $this->machine->apply($this->shipping, 'ship');
        $this->assertEquals(DefaultShippingState::in_transit, $this->shipping->getShippingState());

        $this->machine->apply($this->shipping, 'deliver');
        $this->assertEquals(DefaultShippingState::delivered, $this->shipping->getShippingState());
    }

    public function test_it_can_create_state_machine_for_merchant_order()
    {
        $merchantOrderShipping = DefaultMerchantOrderShipping::fromMappedData(array_merge($this->shipping->getMappedData(), [
            'cost' => $this->shipping->getShippingCost(),
            'shipping_state' => $this->shipping->getShippingState()
        ]), $this->order->getMappedData(), []);

        $this->assertTrue($this->machine->can($merchantOrderShipping, 'ship'));
        $this->assertFalse($this->machine->can($merchantOrderShipping, 'deliver'));

        $this->machine->apply($this->shipping, 'ship');
        $this->assertEquals(DefaultShippingState::in_transit, $this->shipping->getShippingState());
    }

    public function test_it_can_create_machine_with_default_transitions()
    {
        $machine = new ShippingStateMachine(DefaultShippingState::cases(), DefaultShippingState::getTransitions());
        $machine->setOrder($this->order);

        $machine->apply($this->shipping, 'start_packing');
        $this->assertEquals(DefaultShippingState::ready_for_packing, $this->shipping->getShippingState());
    }
}
