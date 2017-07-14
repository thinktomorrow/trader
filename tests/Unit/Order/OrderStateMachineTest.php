<?php

namespace Thinktomorrow\Trader\Unit;

use Thinktomorrow\Trader\Common\Domain\State\StateException;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Order\Domain\OrderId;
use Thinktomorrow\Trader\Order\Domain\OrderState;
use Thinktomorrow\Trader\Tests\Unit\UnitTestCase;

class OrderStateMachineTest extends UnitTestCase
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
    function it_can_apply_transition()
    {
        $this->assertEquals('new',$this->order->state());

        $this->machine->apply('create');
        $this->assertEquals('pending',$this->order->state());

        $this->machine->apply('abandon');
        $this->assertEquals('abandoned',$this->order->state());
    }

    /** @test */
    function it_cannot_change_to_invalid_state_directly()
    {
        $this->setExpectedException(StateException::class);

        $this->order->changeState('foobar');
    }

    /** @test */
    function it_only_allows_transition_to_allowed_state()
    {
        $this->setExpectedException(StateException::class);

        $this->order->changeState('confirmed');
    }
}