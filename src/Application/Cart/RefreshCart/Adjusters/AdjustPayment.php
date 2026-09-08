<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Application\Cart\PaymentMethod\UpdatePaymentMethodOnOrder;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

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
}
