<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Application\Cart\PaymentMethod\UpdatePaymentMethodOnOrder;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;

final class AdjustPayment implements Adjuster
{
    public function __construct(private UpdatePaymentMethodOnOrder $updatePaymentMethodOnOrder) {}

    public function adjust(Order $order): void
    {
        if ($order->getPayments() === []) {
            return;
        }

        $paymentMethodId = $order->getPayments()[0]->getPaymentMethodId();

        $this->updatePaymentMethodOnOrder->refresh($order, $paymentMethodId);
    }

    /**
     * Load the selected profile once for reuse across promo combination trials.
     * This profile can then be used by the method adjustWithProfile
     */
    public function selectedMethodFor(Order $order): ?PaymentMethod
    {
        $payment = $order->getPayments()[0] ?? null;

        return $payment === null
            ? null
            : $this->updatePaymentMethodOnOrder->findForRefresh($payment->getPaymentMethodId());
    }

    /**
     * Refresh payment on this order using the preloaded profile, without another lookup.
     * This is used by promo evaluation to reduce memory overload on n+1 lookups
     */
    public function adjustWithMethod(Order $order, ?PaymentMethod $method): void
    {
        if ($order->getPayments() === []) {
            return;
        }

        $this->updatePaymentMethodOnOrder->refreshWithMethod($order, $method);
    }
}
