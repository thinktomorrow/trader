<?php

namespace Thinktomorrow\Trader\Application\Stock\Read;

interface Stockable
{
    public function inStock(int $level = 1): bool;

    /**
     * Allow to be bought even when order exceeds the
     * stock levels or when stock is depleted.
     *
     * @return bool
     */
    public function ignoreOutOfStock(): bool;

    public function getStockLevel(): int;
}
