<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\State\Payment;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentId;

trait HasPaymentStateValues
{
    private string $orderId;
    private string $paymentId;
    private array $data;

    public function __construct(string $orderId, string $paymentId, array $data = [])
    {
        $this->orderId = $orderId;
        $this->paymentId = $paymentId;
        $this->data = $data;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getPaymentId(): PaymentId
    {
        return PaymentId::fromString($this->paymentId);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
