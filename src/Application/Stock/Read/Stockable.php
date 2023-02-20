<?php

namespace Thinktomorrow\Trader\Application\Stock\Read;

interface Stockable
{
    public function inStock(int $level = 1): bool;

    /**
     * Allow to be purchased even when order exceeds
     * the stock levels or when stock is depleted.
     *
     * @return bool
     */
    public function ignoresOutOfStock(): bool;

    public function getStockLevel(): int;
}
