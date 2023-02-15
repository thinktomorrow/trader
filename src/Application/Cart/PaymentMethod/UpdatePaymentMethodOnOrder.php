<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\PaymentMethod;

use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodRepository;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodState;
use Thinktomorrow\Trader\TraderConfig;

class UpdatePaymentMethodOnOrder
{
    private TraderConfig $config;
    private OrderRepository $orderRepository;
    private PaymentMethodRepository $paymentMethodRepository;

    public function __construct(TraderConfig $config, OrderRepository $orderRepository, PaymentMethodRepository $paymentMethodRepository)
    {
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function handle(Order $order, PaymentMethodId $paymentMethodId): void
    {
        $paymentMethod = $this->paymentMethodRepository->find($paymentMethodId);

        if (! in_array($paymentMethod->getState(), PaymentMethodState::onlineStates())) {
            $this->removePaymentMethodFromOrder($order);

            return;
        }

        $billingCountryId = $order->getBillingAddress()?->getAddress()->countryId;

        if ($billingCountryId && $paymentMethod->hasAnyCountries() && ! $paymentMethod->hasCountry($billingCountryId)) {
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
