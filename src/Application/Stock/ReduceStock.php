<?php

namespace Thinktomorrow\Trader\Application\Stock;

use Thinktomorrow\Trader\Domain\Model\Stock\StockItemId;

class ReduceStock
{
    private string $stockItemId;
    private int $amount;

    public function __construct(string $stockItemId, int $amount)
    {
        $this->stockItemId = $stockItemId;
        $this->amount = $amount;
    }

    public function getStockItemId(): StockItemId
    {
        return StockItemId::fromString($this->stockItemId);
    }

    public function getAmount(): int
    {
        return $this->amount;
    }
}
