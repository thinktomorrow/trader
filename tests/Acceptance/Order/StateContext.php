<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order;

use Tests\Acceptance\TestCase;
use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Cart\CartApplication;
use Thinktomorrow\Trader\Application\Cart\PaymentMethod\UpdatePaymentMethodOnOrder;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\RefreshCartAction;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\UpdateShippingProfileOnOrder;
use Thinktomorrow\Trader\Application\Order\State\OrderStateApplication;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentStateMachine;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentStateToEventMap;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingStateMachine;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingStateToEventMap;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderState;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderStateMachine;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderStateToEventMap;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCustomerRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryPaymentMethodRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryShippingProfileRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

abstract class StateContext extends TestCase
{
    use TestHelpers;

    protected InMemoryOrderRepository $orderRepository;
    protected PaymentStateMachine $paymentStateMachine;
    protected ShippingStateMachine $shippingStateMachine;
    protected OrderStateApplication $orderStateApplication;
    protected EventDispatcherSpy $eventDispatcher;
    protected OrderStateMachine $orderStateMachine;
    protected CartApplication $cartApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = new InMemoryOrderRepository();
        $this->orderStateMachine = new OrderStateMachine(OrderState::cases(), OrderState::getDefaultTransitions());
        $this->paymentStateMachine = new PaymentStateMachine(PaymentState::cases(), PaymentState::getDefaultTransitions());
        $this->shippingStateMachine = new ShippingStateMachine(ShippingState::cases(), ShippingState::getDefaultTransitions());

        OrderStateToEventMap::set(OrderStateToEventMap::getDefaultMapping());
        PaymentStateToEventMap::set(PaymentStateToEventMap::getDefaultMapping());
        ShippingStateToEventMap::set(ShippingStateToEventMap::getDefaultMapping());

        $this->orderStateApplication = new OrderStateApplication(
            $this->orderRepository,
            $this->orderStateMachine,
            $this->paymentStateMachine,
            $this->shippingStateMachine,
            $this->eventDispatcher = new EventDispatcherSpy()
        );

        // TODO: unify this instantiation in the test container
        $this->cartApplication = new CartApplication(
            new TestTraderConfig(),
            new TestContainer(),
            new InMemoryVariantRepository(),
            $this->orderRepository,
            $this->orderStateMachine,
            new RefreshCartAction(),
            new InMemoryShippingProfileRepository(),
            new UpdateShippingProfileOnOrder(new TestTraderConfig(), $this->orderRepository, new InMemoryShippingProfileRepository()),
            TestContainer::make(UpdatePaymentMethodOnOrder::class),
            new InMemoryCustomerRepository(),
            $this->eventDispatcher,
        );
    }

    protected function assertOrderStateTransition(string $transitionMethod, OrderState $currentState, OrderState $newState, $useCartApplication = false)
    {
        $order = $this->createOrder(['order_id' => 'xxx', 'order_state' => $currentState->value]);
        $this->orderRepository->save($order);


        if($useCartApplication) {
            $transitionClass = 'Thinktomorrow\\Trader\\Application\\Cart\\' . ucfirst($transitionMethod);
            $this->cartApplication->$transitionMethod(new $transitionClass($order->orderId->get()));
        } else {
            $transitionClass = 'Thinktomorrow\\Trader\\Application\\Order\\State\\Order\\' . ucfirst($transitionMethod);
            $this->orderStateApplication->$transitionMethod(new $transitionClass($order->orderId->get()));
        }

        $order = $this->orderRepository->find($order->orderId);
        $this->assertEquals($newState, $order->getOrderState());
    }

    protected function assertCartStateTransition(string $transitionMethod, OrderState $currentState, OrderState $newState)
    {
        $this->assertOrderStateTransition($transitionMethod, $currentState, $newState, true);
    }

    protected function assertPaymentStateTransition(string $transitionMethod, PaymentState $currentState, PaymentState $newState, ?OrderState $orderState = null, ?OrderState $newOrderState = null)
    {
        if (! $orderState) {
            $orderState = OrderState::confirmed;
        }

        $order = $this->createOrder(['order_id' => 'xxx', 'order_state' => $orderState->value], [], [], [], [
            $payment = $this->createOrderPayment(['payment_state' => $currentState->value]),
        ]);
        $this->orderRepository->save($order);

        $transitionClass = 'Thinktomorrow\\Trader\\Application\\Order\\State\\Payment\\' . ucfirst($transitionMethod);
        $this->orderStateApplication->$transitionMethod(new $transitionClass($order->orderId->get(), $payment->paymentId->get()));

        $order = $this->orderRepository->find($order->orderId);
        $this->assertEquals($newState, $order->getPayments()[0]->getPaymentState());

        if ($newOrderState) {
            $this->assertEquals($newOrderState, $order->getOrderState());
        }
    }

    protected function assertShippingStateTransition(string $transitionMethod, ShippingState $currentState, ShippingState $newState, ?OrderState $orderState = null, ?OrderState $newOrderState = null)
    {
        if (! $orderState) {
            $orderState = OrderState::confirmed;
        }

        $order = $this->createOrder(['order_id' => 'xxx', 'order_state' => $orderState->value], [], [], [
            $shipping = $this->createOrderShipping(['shipping_state' => $currentState->value]),
        ]);
        $this->orderRepository->save($order);

        $transitionClass = 'Thinktomorrow\\Trader\\Application\\Order\\State\\Shipping\\' . ucfirst($transitionMethod);
        $this->orderStateApplication->$transitionMethod(new $transitionClass($order->orderId->get(), $shipping->shippingId->get()));

        $order = $this->orderRepository->find($order->orderId);
        $this->assertEquals($newState, $order->getShippings()[0]->getShippingState());

        if ($newOrderState) {
            $this->assertEquals($newOrderState, $order->getOrderState());
        }
    }
}
