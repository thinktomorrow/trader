<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder;

use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderBillingAddress;

class DefaultMerchantOrderBillingAddress extends DefaultAddress implements MerchantOrderBillingAddress
{
}
