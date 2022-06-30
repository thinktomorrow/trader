<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder;

use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShippingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\Address;

class DefaultMerchantOrderShippingAddress extends Address implements MerchantOrderShippingAddress
{
}
