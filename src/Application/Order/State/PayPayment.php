<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\State;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentId;

final class PayPayment
{
    private string $orderId;
    private string $paymentId;

    public function __construct(string $orderId, string $paymentId)
    {
        $this->orderId = $orderId;
        $this->paymentId = $paymentId;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getPaymentId(): PaymentId
    {
        return PaymentId::fromString($this->paymentId);
    }
}
