<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentId;

final class PayOrder
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
