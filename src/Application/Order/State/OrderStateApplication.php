<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\State;

use Thinktomorrow\Trader\Application\Order\State\Order\AbandonOrder;
use Thinktomorrow\Trader\Application\Order\State\Order\CancelOrder;
use Thinktomorrow\Trader\Application\Order\State\Order\CancelOrderByMerchant;
use Thinktomorrow\Trader\Application\Order\State\Order\ConfirmQuotedOrder;
use Thinktomorrow\Trader\Application\Order\State\Order\DeleteOrder;
use Thinktomorrow\Trader\Application\Order\State\Order\DeliverOrder;
use Thinktomorrow\Trader\Application\Order\State\Order\MarkOrderPaidByMerchant;
use Thinktomorrow\Trader\Application\Order\State\Order\PackOrder;
use Thinktomorrow\Trader\Application\Order\State\Order\PartiallyDeliverOrder;
use Thinktomorrow\Trader\Application\Order\State\Order\PartiallyPackOrder;
use Thinktomorrow\Trader\Application\Order\State\Order\PartiallyPayOrder;
use Thinktomorrow\Trader\Application\Order\State\Order\PayOrder;
use Thinktomorrow\Trader\Application\Order\State\Order\QuoteOrder;
use Thinktomorrow\Trader\Application\Order\State\Order\ReviveOrder;
use Thinktomorrow\Trader\Application\Order\State\Payment\CancelPayment;
use Thinktomorrow\Trader\Application\Order\State\Payment\ChargeBackPayment;
use Thinktomorrow\Trader\Application\Order\State\Payment\ExpirePayment;
use Thinktomorrow\Trader\Application\Order\State\Payment\InitializePayment;
use Thinktomorrow\Trader\Application\Order\State\Payment\PayPayment;
use Thinktomorrow\Trader\Application\Order\State\Payment\PayPaymentByMerchant;
use Thinktomorrow\Trader\Application\Order\State\Payment\RefundPayment;
use Thinktomorrow\Trader\Application\Order\State\Payment\RefundPaymentByMerchant;
use Thinktomorrow\Trader\Application\Order\State\Shipping\DeliverShipment;
use Thinktomorrow\Trader\Application\Order\State\Shipping\HaltPackingShipment;
use Thinktomorrow\Trader\Application\Order\State\Shipping\PackShipment;
use Thinktomorrow\Trader\Application\Order\State\Shipping\ReturnShipment;
use Thinktomorrow\Trader\Application\Order\State\Shipping\ShipShipment;
use Thinktomorrow\Trader\Application\Order\State\Shipping\StartPackingShipment;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentStateMachine;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingStateMachine;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderStateMachine;

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

    public function deleteOrder(DeleteOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'delete', $command->getData());
    }

    public function abandonOrder(AbandonOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'abandon', $command->getData());
    }

    public function reviveOrder(ReviveOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'revive', $command->getData());
    }

    public function cancelOrder(CancelOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'cancel', $command->getData());
    }

    public function cancelOrderByMerchant(CancelOrderByMerchant $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'cancel_by_merchant', $command->getData());
    }

    public function quoteOrder(QuoteOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'quote', $command->getData());
    }

    public function confirmQuotedOrder(ConfirmQuotedOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'confirm_quote', $command->getData());
    }

    public function payOrder(PayOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'pay', $command->getData());
    }

    public function partiallyPayOrder(PartiallyPayOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'partially_pay', $command->getData());
    }

    public function markOrderPaidByMerchant(MarkOrderPaidByMerchant $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'mark_paid_by_merchant', $command->getData());
    }

    public function packOrder(PackOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'pack', $command->getData());
    }

    public function partiallyPackOrder(PartiallyPackOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'partially_pack', $command->getData());
    }

    public function deliverOrder(DeliverOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'deliver', $command->getData());
    }

    public function partiallyDeliverOrder(PartiallyDeliverOrder $command): void
    {
        $this->handleOrderStateEvent($command->getOrderId(), 'partially_deliver', $command->getData());
    }

    public function initializePayment(InitializePayment $command): void
    {
        $this->handlePaymentStateEvent($command->getOrderId(), $command->getPaymentId(), 'initialize', $command->getData());
    }

    public function payPayment(PayPayment $command): void
    {
        $this->handlePaymentStateEvent($command->getOrderId(), $command->getPaymentId(), 'pay', $command->getData());
    }

    public function payPaymentByMerchant(PayPaymentByMerchant $command): void
    {
        $this->handlePaymentStateEvent($command->getOrderId(), $command->getPaymentId(), 'pay_by_merchant', $command->getData());
    }

    public function cancelPayment(CancelPayment $command): void
    {
        $this->handlePaymentStateEvent($command->getOrderId(), $command->getPaymentId(), 'cancel', $command->getData());
    }

    public function expirePayment(ExpirePayment $command): void
    {
        $this->handlePaymentStateEvent($command->getOrderId(), $command->getPaymentId(), 'expire', $command->getData());
    }

    public function refundPayment(RefundPayment $command): void
    {
        $this->handlePaymentStateEvent($command->getOrderId(), $command->getPaymentId(), 'refund', $command->getData());
    }

    public function refundPaymentByMerchant(RefundPaymentByMerchant $command): void
    {
        $this->handlePaymentStateEvent($command->getOrderId(), $command->getPaymentId(), 'refund_by_merchant', $command->getData());
    }

    public function chargeBackPayment(ChargeBackPayment $command): void
    {
        $this->handlePaymentStateEvent($command->getOrderId(), $command->getPaymentId(), 'charge_back', $command->getData());
    }

    public function startPackingShipment(StartPackingShipment $command): void
    {
        $this->handleShippingStateEvent($command->getOrderId(), $command->getShippingId(), 'start_packing', $command->getData());
    }

    public function haltPackingShipment(HaltPackingShipment $command): void
    {
        $this->handleShippingStateEvent($command->getOrderId(), $command->getShippingId(), 'halt_packing', $command->getData());
    }

    public function packShipment(PackShipment $command): void
    {
        $this->handleShippingStateEvent($command->getOrderId(), $command->getShippingId(), 'pack', $command->getData());
    }

    public function shipShipment(ShipShipment $command): void
    {
        $this->handleShippingStateEvent($command->getOrderId(), $command->getShippingId(), 'ship', $command->getData());
    }

    public function deliverShipment(DeliverShipment $command): void
    {
        $this->handleShippingStateEvent($command->getOrderId(), $command->getShippingId(), 'deliver', $command->getData());
    }

    public function returnShipment(ReturnShipment $command): void
    {
        $this->handleShippingStateEvent($command->getOrderId(), $command->getShippingId(), 'return', $command->getData());
    }

    private function handleOrderStateEvent(OrderId $orderId, string $transition, array $data): void
    {
        $order = $this->orderRepository->find($orderId);

        $this->orderStateMachine->apply($order, $transition, $data);

        $this->orderRepository->save($order);
        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    private function handlePaymentStateEvent(OrderId $orderId, PaymentId $paymentId, string $transition, array $data): void
    {
        $order = $this->orderRepository->find($orderId);

        $payment = $order->findPayment($paymentId);

        $this->paymentStateMachine->setOrder($order);
        $this->paymentStateMachine->apply($payment, $transition, $data);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    private function handleShippingStateEvent(OrderId $orderId, ShippingId $shippingId, string $transition, array $data): void
    {
        $order = $this->orderRepository->find($orderId);

        $shipping = $order->findShipping($shippingId);

        $this->shippingStateMachine->setOrder($order);
        $this->shippingStateMachine->apply($shipping, $transition, $data);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }
}
