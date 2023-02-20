<?php

namespace Thinktomorrow\Trader\Domain\Model\Stock\Events;

use Thinktomorrow\Trader\Domain\Model\Stock\StockItemId;

class StockDepleted
{
    public function __construct(
        public readonly StockItemId $stockItemId,
        public readonly int $formerStockLevel,
        public readonly int $newStockLevel
    ) {
    }
}
