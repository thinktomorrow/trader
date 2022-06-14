<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart;

use Thinktomorrow\Trader\Domain\Model\Order\Order;

interface Adjuster
{
    public function adjust(Order $order): void;
}
