<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\PaymentMethod;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\VatRate\OrderServicePriceResolver;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\Exceptions\CouldNotFindPaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodRepository;

class UpdatePaymentMethodOnOrder
{
    private ContainerInterface $container;

    private OrderRepository $orderRepository;

    private PaymentMethodRepository $paymentMethodRepository;

    private VerifyPaymentMethodForCart $verifyPaymentMethodForCart;

    public function __construct(
        ContainerInterface $container,
        OrderRepository $orderRepository,
        VerifyPaymentMethodForCart $verifyPaymentMethodForCart,
        PaymentMethodRepository $paymentMethodRepository,
        private OrderServicePriceResolver $servicePriceResolver,
    ) {
        $this->container = $container;
        $this->orderRepository = $orderRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->verifyPaymentMethodForCart = $verifyPaymentMethodForCart;
    }

    public function handle(Order $order, PaymentMethodId $paymentMethodId): void
    {
        $paymentMethod = $this->paymentMethodRepository->find($paymentMethodId);

        $this->applyPaymentMethod($order, $paymentMethod);
    }

    public function refresh(Order $order, PaymentMethodId $paymentMethodId): void
    {
        try {
            $paymentMethod = $this->paymentMethodRepository->find($paymentMethodId);
        } catch (CouldNotFindPaymentMethod) {
            $this->removePaymentMethodFromOrder($order);

            return;
        }

        $this->applyPaymentMethod($order, $paymentMethod);
    }

    private function applyPaymentMethod(Order $order, PaymentMethod $paymentMethod): void
    {
        if (! $this->verifyPaymentMethodForCart->verify($order, $paymentMethod)) {
            $this->removePaymentMethodFromOrder($order);

            return;
        }

        $paymentCost = $this->servicePriceResolver->resolvePaymentCost($order, $paymentMethod);

        if (count($order->getPayments()) > 0) {
            $existingPayment = $order->getPayments()[0];
            $existingPayment->updatePaymentMethod($paymentMethod->paymentMethodId);
            $existingPayment->updateCost($paymentCost);
            $existingPayment->addData(array_merge($paymentMethod->getData(), ['provider_id' => $paymentMethod->getProvider()->get()]));

            $order->updatePayment($existingPayment);
        } else {
            $payment = Payment::create(
                $order->orderId,
                $this->orderRepository->nextPaymentReference(),
                $paymentMethod->paymentMethodId,
                $this->container->get(PaymentState::class)::getDefaultState(),
                $paymentCost
            );

            $payment->addData(array_merge($paymentMethod->getData(), ['provider_id' => $paymentMethod->getProvider()->get()]));

            $order->addPayment($payment);
        }
    }

    private function removePaymentMethodFromOrder(Order $order): void
    {
        foreach ($order->getPayments() as $payment) {
            $order->deletePayment($payment->paymentId);
        }
    }
}
