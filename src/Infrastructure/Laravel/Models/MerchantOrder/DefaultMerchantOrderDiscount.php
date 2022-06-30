<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder;

use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderDiscount;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\OrderReadDiscount;

class DefaultMerchantOrderDiscount extends OrderReadDiscount implements MerchantOrderDiscount
{
}
