<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder;

use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShippingAddress;

class DefaultMerchantOrderShippingAddress extends DefaultAddress implements MerchantOrderShippingAddress
{
}
