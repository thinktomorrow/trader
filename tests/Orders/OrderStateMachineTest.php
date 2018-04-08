<?php

namespace Thinktomorrow\Trader\Unit;

use Thinktomorrow\Trader\Common\State\StateException;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Orders\Domain\OrderState;
use Thinktomorrow\Trader\Tests\TestCase;

class OrderStateMachineTest extends TestCase
{
    private $order;
    private $machine;

    public function setUp()
    {
        parent::setUp();

        $this->order = new Order(OrderId::fromInteger(1));
        $this->machine = new OrderState($this->order);
    }

    /** @test */
    public function it_can_apply_transition()
    {
        $this->assertEquals('new', $this->order->state());

        $this->machine->apply('create');
        $this->assertEquals('pending', $this->order->state());

        $this->machine->apply('abandon');
        $this->assertEquals('abandoned', $this->order->state());
    }

    /** @test */
    public function it_cannot_change_to_invalid_state_directly()
    {
        $this->expectException(StateException::class);

        $this->order->changeState('foobar');
    }

    /** @test */
    public function it_ignores_change_to_current_state()
    {
        $this->assertEquals('new', $this->order->state());
        $this->order->changeState('new');
        $this->assertEquals('new', $this->order->state());
    }

    /** @test */
    public function it_only_allows_transition_to_allowed_state()
    {
        $this->expectException(StateException::class);

        $this->order->changeState('confirmed');
    }

    /** @test */
    public function it_can_pay_a_confirmed_order()
    {
        // On paid state, customer hands over control to merchant
        $order = new Order(OrderId::fromInteger(1));
        $order->forceState(OrderState::CONFIRMED);
        $this->machine = new OrderState($order);

        $this->machine->apply('pay');

        $this->assertEquals(OrderState::PAID, $order->state());
    }

    /** @test */
    public function it_tells_when_order_is_still_in_customer_hands_as_cart()
    {
        $this->assertTrue($this->order->inCustomerHands());
        $this->assertFalse($this->order->inMerchantHands());
    }

    /** @test */
    public function it_can_queue_process_if_order_is_paid()
    {
        $this->order->forceState(OrderState::PAID);
        $this->assertEquals('paid', $this->order->state());

        $this->machine->apply('queue');
        $this->assertEquals(OrderState::QUEUED_FOR_PROCESS, $this->order->state());
    }
}
