<?php

namespace Tests\Unit\Common\State;

use Tests\TestHelpers;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrder;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderPayment;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShipping;
use Thinktomorrow\Trader\Domain\Common\State\StateException;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentStateMachine;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingStateMachine;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderStateMachine;

class OrderStateMachineTest extends TestCase
{
    use TestHelpers;

    private Order $order;

    private OrderStateMachine $machine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->order = $this->orderContext->createDefaultOrder();
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

    public function test_it_can_apply_transition()
    {
        $this->assertSame(DefaultOrderState::cart_pending, $this->order->getOrderState());

        $this->machine->apply($this->order, 'confirm');
        $this->assertSame(DefaultOrderState::confirmed, $this->order->getOrderState());
    }

    public function test_it_cannot_change_to_invalid_state()
    {
        $this->expectException(StateException::class);

        $this->machine->apply($this->order, 'foobar');
    }

    public function test_it_only_allows_transition_to_allowed_state()
    {
        $this->expectException(StateException::class);

        $this->machine->apply($this->order, 'pay');
        $this->assertSame(DefaultOrderState::cart_pending, $this->order->getOrderState());
    }

    public function test_it_throws_exception_if_transition_map_is_malformed()
    {
        $this->expectException(StateException::class);

        new OrderStateMachine([DefaultOrderState::cart_pending], [
            'confirm' => [
                'from' => [DefaultOrderState::cart_pending],
            ],
        ]);
    }

    public function test_it_throws_exception_if_transition_contains_invalid_state()
    {
        $this->expectException(StateException::class);

        new OrderStateMachine([], [
            'confirm' => [
                'from' => [DefaultOrderState::cart_pending],
            ],
        ]);
    }

    public function test_order_state_resolution_requires_at_least_one_configured_state(): void
    {
        $this->expectException(\LogicException::class);

        $this->invokeGetState(new OrderStateMachine([], []), $this->createMock(MerchantOrder::class));
    }

    public function test_payment_state_resolution_requires_at_least_one_configured_state(): void
    {
        $this->expectException(\LogicException::class);

        $this->invokeGetState(new PaymentStateMachine([], []), $this->createMock(MerchantOrderPayment::class));
    }

    public function test_shipping_state_resolution_requires_at_least_one_configured_state(): void
    {
        $this->expectException(\LogicException::class);

        $this->invokeGetState(new ShippingStateMachine([], []), $this->createMock(MerchantOrderShipping::class));
    }

    private function invokeGetState(object $stateMachine, object $model): void
    {
        (new \ReflectionMethod($stateMachine, 'getState'))->invoke($stateMachine, $model);
    }
}
