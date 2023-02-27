<?php

namespace Tests\Unit\Common\State;

use Tests\TestHelpers;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\State\StateException;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderStateMachine;

class OrderStateMachineTest extends TestCase
{
    use TestHelpers;

    private \Thinktomorrow\Trader\Domain\Model\Order\Order $order;
    private OrderStateMachine $machine;

    public function setUp(): void
    {
        parent::setUp();

        $this->order = $this->createDefaultOrder();
        $this->order->updateState(DefaultOrderState::cart_pending);

        $this->machine = new OrderStateMachine([
            DefaultOrderState::cart_pending,
            DefaultOrderState::confirmed,
            DefaultOrderState::paid,
        ], [
            'confirm' => [
                'from' => [DefaultOrderState::cart_pending],
                'to' => DefaultOrderState::confirmed,
            ],
            'pay' => [
                'from' => [DefaultOrderState::confirmed],
                'to' => DefaultOrderState::paid,
            ],
        ]);
    }

    /** @test */
    public function it_can_apply_transition()
    {
        $this->assertSame(DefaultOrderState::cart_pending, $this->order->getOrderState());

        $this->machine->apply($this->order, 'confirm');
        $this->assertSame(DefaultOrderState::confirmed, $this->order->getOrderState());
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
        $this->assertSame(DefaultOrderState::cart_pending, $this->order->getOrderState());
    }

    /** @test */
    public function it_throws_exception_if_transition_map_is_malformed()
    {
        $this->expectException(StateException::class);

        new OrderStateMachine([DefaultOrderState::cart_pending], [
            'confirm' => [
                'from' => [DefaultOrderState::cart_pending],
            ],
        ]);
    }

    /** @test */
    public function it_throws_exception_if_transition_contains_invalid_state()
    {
        $this->expectException(StateException::class);

        new OrderStateMachine([], [
            'confirm' => [
                'from' => [DefaultOrderState::cart_pending],
            ],
        ]);
    }
}
