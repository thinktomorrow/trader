<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\RefreshCart;

use Thinktomorrow\Trader\Domain\Common\Context;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

interface Adjuster
{
    public function adjust(Order $order, Context $context): void;
}
