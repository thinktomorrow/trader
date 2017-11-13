<?php

namespace Thinktomorrow\Trader\Unit;

use Thinktomorrow\Trader\Common\Domain\State\StateException;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Orders\Domain\OrderState;
use Thinktomorrow\Trader\Tests\UnitTestCase;

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
    public function it_tells_when_order_is_still_in_customer_hands_as_cart()
    {
        $this->assertTrue($this->order->inCustomerHands());
        $this->assertFalse($this->order->inMerchantHands());
    }
}
