<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Shipping;

interface ShippingStateReader
{
    public function getShippingState(): string;
}
