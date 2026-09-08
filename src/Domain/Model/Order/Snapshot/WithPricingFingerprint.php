<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Snapshot;

trait WithPricingFingerprint
{
    public function getPricingFingerprint(): string
    {
        return OrderPricingFingerprint::calculate($this);
    }
}
