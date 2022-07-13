<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder;

use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderPayment;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\OrderReadPayment;

class DefaultMerchantOrderPayment extends OrderReadPayment implements MerchantOrderPayment
{
    public function getPaymentState(): string
    {
        return $this->state->getValueAsString();
    }
}
