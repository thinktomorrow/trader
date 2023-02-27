<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;

final class PaymentInitialized
{
    public function __construct(
        public readonly OrderId      $orderId,
        public readonly PaymentId    $paymentId,
        public readonly PaymentState $formerPaymentState,
        public readonly PaymentState $newPaymentState,
        public readonly array        $data,
    ) {
    }
}
