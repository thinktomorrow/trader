<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder;

use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShipping;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\OrderReadShipping;

class DefaultMerchantOrderShipping extends OrderReadShipping implements MerchantOrderShipping
{
    public function getShippingState(): string
    {
        return $this->state->getValueAsString();
    }
}
