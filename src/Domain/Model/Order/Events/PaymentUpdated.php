<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Events;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentId;

final class PaymentUpdated
{
    public readonly OrderId $orderId;
    public readonly PaymentId $paymentId;

    public function __construct(OrderId $orderId, PaymentId $paymentId)
    {
        $this->orderId = $orderId;
        $this->paymentId = $paymentId;
    }
}
