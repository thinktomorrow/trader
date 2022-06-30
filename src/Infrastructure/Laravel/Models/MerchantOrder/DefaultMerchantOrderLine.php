<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder;

use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderLine;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\OrderReadLine;

class DefaultMerchantOrderLine extends OrderReadLine implements MerchantOrderLine
{
}
