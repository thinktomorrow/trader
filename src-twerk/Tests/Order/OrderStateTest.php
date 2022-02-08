<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Order;

use Tests\TestCase;
use Thinktomorrow\Trader\Order\Domain\OrderState;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Thinktomorrow\Trader\Common\State\StateException;
use Thinktomorrow\Trader\Order\Domain\OrderStateMachine;

class OrderStateTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_can_init_state()
    {
        $state = OrderState::fromString(OrderState::PAID);

        $this->assertTrue($state->is(OrderState::PAID));
        $this->assertFalse($state->is(OrderState::FULFILLED));
    }

    /** @test */
    public function default_order_state_is_pending()
    {
        $order = $this->storeOrder('xxx');

        $this->assertEquals(OrderState::CART_PENDING, $order->getOrderState());
    }

    /** @test */
    public function it_can_apply_a_transition()
    {
        $order = $this->storeOrder('xxx');

        app()->make(OrderStateMachine::class, ['object' => $order])->apply('confirm');

        $this->assertEquals(OrderState::CONFIRMED, $order->getOrderState());
    }

    /** @test */
    public function it_cannot_apply_an_invalid_transition()
    {
        $this->expectException(StateException::class);

        $order = $this->storeOrder('xxx');

        app()->make(OrderStateMachine::class, ['object' => $order])->apply('pay');

        $this->assertEquals(OrderState::CART_PENDING, $order->getOrderState());
    }

    /** @test */
    public function it_can_query_records_scoped_by_state()
    {
        // TODO: put in repo test
        $this->markTestIncomplete();
    }
}
