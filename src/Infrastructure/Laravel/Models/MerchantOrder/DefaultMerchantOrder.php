<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder;

use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrder;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\OrderRead;

class DefaultMerchantOrder extends OrderRead implements MerchantOrder
{
}
