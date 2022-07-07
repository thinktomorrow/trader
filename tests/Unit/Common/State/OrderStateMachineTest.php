<?php

namespace Tests\Unit\Common\State;

use Tests\TestHelpers;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\OrderState;
use Thinktomorrow\Trader\Domain\Common\State\StateException;
use Thinktomorrow\Trader\Domain\Model\Order\OrderStateMachine;

class OrderStateMachineTest extends TestCase
{
    use TestHelpers;

    private \Thinktomorrow\Trader\Domain\Model\Order\Order $order;
    private OrderStateMachine $machine;

    public function setUp(): void
    {
        parent::setUp();

        $this->order = $this->createdOrder();
        $this->order->updateState(OrderState::cart_pending);

        $this->machine = new OrderStateMachine([
            OrderState::cart_pending,
            OrderState::confirmed,
            OrderState::paid,
        ], [
            'confirm' => [
                'from' => [OrderState::cart_pending],
                'to' => OrderState::confirmed,
            ],
            'pay' => [
                'from' => [OrderState::confirmed],
                'to' => OrderState::paid,
            ],
        ]);
    }

    /** @test */
    public function it_can_apply_transition()
    {
        $this->assertSame(OrderState::cart_pending, $this->order->getOrderState());

        $this->machine->apply($this->order, 'confirm');
        $this->assertSame(OrderState::confirmed, $this->order->getOrderState());
    }

    /** @test */
    public function it_cannot_change_to_invalid_state()
    {
        $this->expectException(StateException::class);

        $this->machine->apply($this->order, 'foobar');
    }

    /** @test */
    public function it_only_allows_transition_to_allowed_state()
    {
        $this->expectException(StateException::class);

        $this->machine->apply($this->order, 'pay');
        $this->assertSame(OrderState::cart_pending, $this->order->getOrderState());
    }

    /** @test */
    public function it_throws_exception_if_transition_map_is_malformed()
    {
        $this->expectException(StateException::class);

        new OrderStateMachine([OrderState::cart_pending], [
            'confirm' => [
                'from' => [OrderState::cart_pending],
            ],
        ]);
    }

    /** @test */
    public function it_throws_exception_if_transition_contains_invalid_state()
    {
        $this->expectException(StateException::class);

        new OrderStateMachine([], [
            'confirm' => [
                'from' => [OrderState::cart_pending],
            ],
        ]);
    }
}
