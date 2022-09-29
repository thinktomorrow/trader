<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder;

use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderLinePersonalisation;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\OrderReadLinePersonalisation;

class DefaultMerchantOrderLinePersonalisation extends OrderReadLinePersonalisation implements MerchantOrderLinePersonalisation
{
}
