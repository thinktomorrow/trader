<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\State;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Order\OrderStateMachine;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentStateMachine;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingStateMachine;

final class OrderStateApplication
{
    private OrderRepository $orderRepository;
    private OrderStateMachine $orderStateMachine;
    private EventDispatcher $eventDispatcher;
    private PaymentStateMachine $paymentStateMachine;
    private ShippingStateMachine $shippingStateMachine;

    public function __construct(OrderRepository $orderRepository, OrderStateMachine $orderStateMachine, PaymentStateMachine $paymentStateMachine, ShippingStateMachine $shippingStateMachine, EventDispatcher $eventDispatcher)
    {
        $this->orderRepository = $orderRepository;
        $this->orderStateMachine = $orderStateMachine;
        $this->eventDispatcher = $eventDispatcher;
        $this->paymentStateMachine = $paymentStateMachine;
        $this->shippingStateMachine = $shippingStateMachine;
    }

    public function payOrder(PayOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'pay');
    }

    public function partiallyPayOrder(PartiallyPayOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'partially_pay');
    }

    public function packOrder(PackOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'pack');
    }

    public function partiallyPackOrder(PartiallyPackOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'partially_pack');
    }

    public function deliverOrder(DeliverOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'deliver');
    }

    public function partiallyDeliverOrder(PartiallyDeliverOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'partially_deliver');
    }

    public function fulfillOrder(FulfillOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'fulfill');
    }

    public function partiallyPayPayment(PartiallyPayPayment $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'partially_pay');
    }

    private function handleOrderStateEvent(OrderId $orderId, string $transition): void
    {
        $order = $this->orderRepository->find($orderId);

        $this->orderStateMachine->apply($order, $transition);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    private function handlePaymentStateEvent(OrderId $orderId, PaymentId $paymentId, string $transition): void
    {
        $order = $this->orderRepository->find($orderId);

        $payment = $order->findPayment($paymentId);

        $this->paymentStateMachine->apply($payment, $transition);

        // Is this necessary???
        $order->updatePayment($payment);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    private function handleShippingStateEvent(OrderId $orderId, ShippingId $shippingId, string $transition): void
    {
        $order = $this->orderRepository->find($orderId);

        $shipping = $order->findShipping($shippingId);

        $this->shippingStateMachine->apply($shipping, $transition);

        // Is this necessary???
        $order->updateShipping($shipping);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }
}
