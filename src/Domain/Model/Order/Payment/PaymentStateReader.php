<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Payment;

interface PaymentStateReader
{
    public function getPaymentState(): string;
}
