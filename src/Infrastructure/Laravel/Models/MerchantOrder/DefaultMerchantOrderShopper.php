<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder;

use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShopper;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\OrderReadShopper;

class DefaultMerchantOrderShopper extends OrderReadShopper implements MerchantOrderShopper
{
}
