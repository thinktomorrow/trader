<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

final class AdjustDiscountsAndServices implements Adjuster
{
    public function __construct(
        private AdjustDiscounts $adjustDiscounts,
    ) {}

    public function adjust(Order $order): void
    {
        $this->adjustDiscounts->adjust($order);
    }
}
