<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order\States;

use Tests\Acceptance\TestCase;
use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\DefaultPaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\DefaultShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;

abstract class StateContext extends TestCase
{
    use TestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function assertOrderStateTransition(string $transitionMethod, DefaultOrderState $currentState, DefaultOrderState $newState, $useCartApplication = false)
    {
        $order = $this->orderContext->createOrder('order-aaa', $currentState->getValueAsString());

        if ($useCartApplication) {
            $transitionClass = 'Thinktomorrow\\Trader\\Application\\Cart\\' . ucfirst($transitionMethod);
            $this->orderContext->apps()->cartApplication()->$transitionMethod(new $transitionClass($order->orderId->get()));
        } else {
            $transitionClass = 'Thinktomorrow\\Trader\\Application\\Order\\State\\Order\\' . ucfirst($transitionMethod);
            $this->orderContext->apps()->orderStateApplication()->$transitionMethod(new $transitionClass($order->orderId->get()));
        }

        $order = $this->orderContext->repos()->orderRepository()->find($order->orderId);
        $this->assertEquals($newState, $order->getOrderState());
    }

    protected function assertCartStateTransition(string $transitionMethod, DefaultOrderState $currentState, DefaultOrderState $newState)
    {
        $this->assertOrderStateTransition($transitionMethod, $currentState, $newState, true);
    }

    protected function assertPaymentStateTransition(string $transitionMethod, DefaultPaymentState $currentState, DefaultPaymentState $newState, ?DefaultOrderState $orderState = null, ?DefaultOrderState $newOrderState = null)
    {
        if (!$orderState) {
            $orderState = DefaultOrderState::confirmed;
        }

        $order = $this->orderContext->createOrder('order-aaa', $orderState->getValueAsString());
        $payment = $this->orderContext->createPayment('order-aaa', 'payment-aaa', [
            'payment_state' => $currentState,
        ]);

        $this->orderContext->addPaymentToOrder($order, $payment);


        $transitionClass = 'Thinktomorrow\\Trader\\Application\\Order\\State\\Payment\\' . ucfirst($transitionMethod);
        $this->orderContext->apps()->orderStateApplication()->$transitionMethod(new $transitionClass($order->orderId->get(), $payment->paymentId->get()));

        $order = $this->orderContext->repos()->orderRepository()->find($order->orderId);
        $this->assertEquals($newState, $order->getPayments()[0]->getPaymentState());

        if ($newOrderState) {
            $this->assertEquals($newOrderState, $order->getOrderState());
        }
    }

    protected function assertShippingStateTransition(string $transitionMethod, DefaultShippingState $currentState, DefaultShippingState $newState, ?DefaultOrderState $orderState = null, ?DefaultOrderState $newOrderState = null)
    {
        if (!$orderState) {
            $orderState = DefaultOrderState::confirmed;
        }

        $order = $this->orderContext->createOrder('order-aaa', $orderState->getValueAsString());
        $shipping = $this->orderContext->createShipping('order-aaa', 'shipping-aaa', [
            'shipping_state' => $currentState,
        ]);

        $this->orderContext->addShippingToOrder($order, $shipping);

        $transitionClass = 'Thinktomorrow\\Trader\\Application\\Order\\State\\Shipping\\' . ucfirst($transitionMethod);
        $this->orderContext->apps()->orderStateApplication()->$transitionMethod(new $transitionClass($order->orderId->get(), $shipping->shippingId->get()));

        $order = $this->orderContext->repos()->orderRepository()->find($order->orderId);
        $this->assertEquals($newState, $order->getShippings()[0]->getShippingState());

        if ($newOrderState) {
            $this->assertEquals($newOrderState, $order->getOrderState());
        }
    }
}
