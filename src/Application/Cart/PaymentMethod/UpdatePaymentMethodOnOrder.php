<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\PaymentMethod;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodRepository;
use Thinktomorrow\Trader\TraderConfig;

class UpdatePaymentMethodOnOrder
{
    private ContainerInterface $container;
    private TraderConfig $config;
    private OrderRepository $orderRepository;
    private PaymentMethodRepository $paymentMethodRepository;
    private VerifyPaymentMethodForCart $verifyPaymentMethodForCart;

    public function __construct(ContainerInterface $container, TraderConfig $config, OrderRepository $orderRepository, VerifyPaymentMethodForCart $verifyPaymentMethodForCart, PaymentMethodRepository $paymentMethodRepository)
    {
        $this->container = $container;
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->verifyPaymentMethodForCart = $verifyPaymentMethodForCart;
    }

    public function handle(Order $order, PaymentMethodId $paymentMethodId): void
    {
        $paymentMethod = $this->paymentMethodRepository->find($paymentMethodId);

        if (! $this->verifyPaymentMethodForCart->verify($order, $paymentMethod)) {
            $this->removePaymentMethodFromOrder($order);

            return;
        }

        $paymentCost = PaymentCost::fromMoney(
            $paymentMethod->getRate(),
            TaxRate::fromString($this->config->getDefaultTaxRate()),
            $this->config->doesTariffInputIncludesVat()
        );

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

    private function removePaymentMethodFromOrder(Order $order)
    {
        foreach ($order->getPayments() as $payment) {
            $order->deletePayment($payment->paymentId);
        }
    }
}
