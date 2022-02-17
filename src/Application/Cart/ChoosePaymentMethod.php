<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;

final class ChoosePaymentMethod
{
    private string $orderId;
    private string $paymentMethodId;

    public function __construct(string $orderId, string $paymentMethodId)
    {
        $this->orderId = $orderId;
        $this->paymentMethodId = $paymentMethodId;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getPaymentMethodId(): PaymentMethodId
    {
        return PaymentMethodId::fromString($this->paymentMethodId);
    }
}
