<?php

namespace Thinktomorrow\Trader\Application\Stock\Read;

class DefaultStockable
{
    public function inStock(int $level = 1): bool
    {
        if ($this->ignoreOutOfStock()) {
            return true;
        }

        return $this->getStockLevel() >= $level;
    }

    /**
     * Allow to be bought even when order exceeds the
     * stock levels or when stock is depleted.
     *
     * @return bool
     */
    public function ignoreOutOfStock(): bool
    {
        return $this->ignore_out_of_stock;
    }

    public function getStockLevel(): int
    {
        return (int) $this->stock_level;
    }
}
