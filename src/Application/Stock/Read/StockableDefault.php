<?php

namespace Thinktomorrow\Trader\Application\Stock\Read;

trait StockableDefault
{
    protected bool $ignore_out_of_stock;
    protected int $stock_level;

    public function inStock(int $level = 1): bool
    {
        return $this->ignoresOutOfStock() ?: $this->getStockLevel() >= $level;
    }

    /**
     * Allow to be bought even when order exceeds the
     * stock levels or when stock is depleted.
     *
     * @return bool
     */
    public function ignoresOutOfStock(): bool
    {
        return $this->ignore_out_of_stock;
    }

    public function getStockLevel(): int
    {
        return $this->stock_level;
    }
}
