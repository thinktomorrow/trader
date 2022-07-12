<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order;

use Tests\TestHelpers;
use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\OrderState;
use Thinktomorrow\Trader\Application\Order\State\PayOrder;
use Thinktomorrow\Trader\Domain\Common\State\StateException;
use Thinktomorrow\Trader\Domain\Model\Order\OrderStateMachine;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Application\Order\State\PartiallyPayOrder;
use Thinktomorrow\Trader\Application\Order\State\OrderStateApplication;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentStateMachine;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingStateMachine;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;

final class OrderStateTest extends TestCase
{
    use TestHelpers;

    private InMemoryOrderRepository $orderRepository;
    private PaymentStateMachine $paymentStateMachine;
    private ShippingStateMachine $shippingStateMachine;
    private OrderStateApplication $orderStateApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = new InMemoryOrderRepository();
        $this->orderStateMachine = new OrderStateMachine(OrderState::cases(), OrderState::getDefaultTransitions());
        $this->paymentStateMachine = new PaymentStateMachine(PaymentState::cases(), PaymentState::getDefaultTransitions());
        $this->shippingStateMachine = new ShippingStateMachine(ShippingState::cases(), ShippingState::getDefaultTransitions());

        $this->orderStateApplication = new OrderStateApplication(
            $this->orderRepository,
            $this->orderStateMachine,
            $this->paymentStateMachine,
            $this->shippingStateMachine,
            $this->eventDispatcher = new EventDispatcherSpy()
        );
    }

    /** @test */
    public function it_can_pay_confirmed_order()
    {
        $this->assertOrderStateTransition('payOrder', OrderState::confirmed, OrderState::paid);
    }

    /** @test */
    public function it_cannot_pay_unconfirmed_cart()
    {
        $this->expectException(StateException::class);
        $this->assertOrderStateTransition('payOrder', OrderState::cart_pending, OrderState::paid);
    }

    /** @test */
    public function it_can_partially_pay_confirmed_order()
    {
        $this->assertOrderStateTransition('partiallyPayOrder', OrderState::confirmed, OrderState::partially_paid);
    }

    /** @test */
    public function it_can_pack_order()
    {
        $this->assertOrderStateTransition('packOrder', OrderState::paid, OrderState::packed);
        $this->assertOrderStateTransition('packOrder', OrderState::partially_paid, OrderState::packed);
    }

    /** @test */
    public function it_can_partially_pack_order()
    {
        $this->assertOrderStateTransition('partiallyPackOrder', OrderState::paid, OrderState::partially_packed);
        $this->assertOrderStateTransition('partiallyPackOrder', OrderState::partially_paid, OrderState::partially_packed);
    }

    /** @test */
    public function it_can_deliver_order()
    {
        $this->assertOrderStateTransition('deliverOrder', OrderState::packed, OrderState::delivered);
        $this->assertOrderStateTransition('deliverOrder', OrderState::partially_packed, OrderState::delivered);
    }

    /** @test */
    public function it_can_partially_deliver_order()
    {
        $this->assertOrderStateTransition('partiallyDeliverOrder', OrderState::packed, OrderState::partially_delivered);
        $this->assertOrderStateTransition('partiallyDeliverOrder', OrderState::partially_packed, OrderState::partially_delivered);
    }

    /** @test */
    public function it_can_fulfill_order()
    {
        $this->assertOrderStateTransition('fulfillOrder', OrderState::delivered, OrderState::fulfilled);
    }

    // 'partially_pay' => [
    //                'from' => [self::confirmed],
    //                'to' => self::partially_paid,
    //            ],
    //            'pay' => [
    //                'from' => [self::confirmed, self::partially_paid],
    //                'to' => self::paid,
    //            ],
    //            'partially_pack' => [
    //                'from' => [self::paid, self::partially_paid],
    //                'to' => self::partially_packed,
    //            ],
    //            'pack' => [
    //                'from' => [self::paid, self::partially_paid, self::partially_packed],
    //                'to' => self::packed,
    //            ],
    //            'partially_deliver' => [
    //                'from' => [self::packed, self::partially_packed],
    //                'to' => self::partially_delivered,
    //            ],
    //            'deliver' => [
    //                'from' => [self::packed, self::partially_packed, self::partially_delivered],
    //                'to' => self::delivered,
    //            ],
    //            'fulfill' => [
    //                'from' => [self::delivered, self::partially_delivered],
    //                'to' => self::fulfilled,
    //            ],

    private function assertOrderStateTransition(string $transitionMethod, OrderState $currentState, OrderState $newState)
    {
        $order = $this->createOrder(['order_id' => 'xxx', 'order_state' => $currentState->value]);
        $this->orderRepository->save($order);

        $transitionClass = 'Thinktomorrow\\Trader\\Application\\Order\\State\\' . ucfirst($transitionMethod);
        $this->orderStateApplication->$transitionMethod(new $transitionClass($order->orderId->get()));

        $order = $this->orderRepository->find($order->orderId);
        $this->assertEquals($newState, $order->getOrderState());
    }
}
