<?php

namespace Thinktomorrow\Trader\Domain\Model\Stock;

interface StockItemRepository
{
    public function findStockItem(StockItemId $stockItemId): StockItem;

    public function saveStockItem(StockItem $stockItem): void;
}
