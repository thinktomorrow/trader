<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder;

use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderBillingAddress;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\Address;

class DefaultMerchantOrderBillingAddress extends Address implements MerchantOrderBillingAddress
{
}
