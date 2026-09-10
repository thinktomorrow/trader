<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\Pricing;

use Thinktomorrow\Trader\Domain\Model\Order\Snapshot\OrderPricingSnapshot;

final class PricingRecalculationResult
{
    public function __construct(
        public readonly ?OrderPricingSnapshot $previous,
        public readonly OrderPricingSnapshot $recalculated,
        public readonly bool $persisted,
    ) {}
}
