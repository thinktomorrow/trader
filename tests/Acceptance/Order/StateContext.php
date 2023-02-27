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
use Thinktomorrow\Trader\Domain\Model\Order\Payment\DefaultPaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentStateMachine;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentStateToEventMap;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\DefaultShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingStateMachine;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingStateToEventMap;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderStateMachine;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderStateToEventMap;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart\DefaultAdjustLine;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCustomerRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;
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
        $this->orderStateMachine = new OrderStateMachine(DefaultOrderState::cases(), DefaultOrderState::getTransitions());
        $this->paymentStateMachine = new PaymentStateMachine(DefaultPaymentState::cases(), DefaultPaymentState::getTransitions());
        $this->shippingStateMachine = new ShippingStateMachine(DefaultShippingState::cases(), DefaultShippingState::getTransitions());

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
            new DefaultAdjustLine(),
            $this->orderRepository,
            $this->orderStateMachine,
            new RefreshCartAction(),
            new InMemoryShippingProfileRepository(),
            new UpdateShippingProfileOnOrder(new TestContainer(), new TestTraderConfig(), $this->orderRepository, new InMemoryShippingProfileRepository()),
            TestContainer::make(UpdatePaymentMethodOnOrder::class),
            new InMemoryCustomerRepository(),
            $this->eventDispatcher,
        );
    }

    protected function assertOrderStateTransition(string $transitionMethod, DefaultOrderState $currentState, DefaultOrderState $newState, $useCartApplication = false)
    {
        $order = $this->createOrder(['order_id' => 'xxx', 'order_state' => $currentState]);
        $this->orderRepository->save($order);


        if ($useCartApplication) {
            $transitionClass = 'Thinktomorrow\\Trader\\Application\\Cart\\' . ucfirst($transitionMethod);
            $this->cartApplication->$transitionMethod(new $transitionClass($order->orderId->get()));
        } else {
            $transitionClass = 'Thinktomorrow\\Trader\\Application\\Order\\State\\Order\\' . ucfirst($transitionMethod);
            $this->orderStateApplication->$transitionMethod(new $transitionClass($order->orderId->get()));
        }

        $order = $this->orderRepository->find($order->orderId);
        $this->assertEquals($newState, $order->getOrderState());
    }

    protected function assertCartStateTransition(string $transitionMethod, DefaultOrderState $currentState, DefaultOrderState $newState)
    {
        $this->assertOrderStateTransition($transitionMethod, $currentState, $newState, true);
    }

    protected function assertPaymentStateTransition(string $transitionMethod, DefaultPaymentState $currentState, DefaultPaymentState $newState, ?DefaultOrderState $orderState = null, ?DefaultOrderState $newOrderState = null)
    {
        if (! $orderState) {
            $orderState = DefaultOrderState::confirmed;
        }

        $order = $this->createOrder(['order_id' => 'xxx', 'order_state' => $orderState], [], [], [], [
            $payment = $this->createOrderPayment(['payment_state' => $currentState]),
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

    protected function assertShippingStateTransition(string $transitionMethod, DefaultShippingState $currentState, DefaultShippingState $newState, ?DefaultOrderState $orderState = null, ?DefaultOrderState $newOrderState = null)
    {
        if (! $orderState) {
            $orderState = DefaultOrderState::confirmed;
        }

        $order = $this->createOrder(['order_id' => 'xxx', 'order_state' => $orderState], [], [], [
            $shipping = $this->createOrderShipping(['shipping_state' => $currentState]),
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
