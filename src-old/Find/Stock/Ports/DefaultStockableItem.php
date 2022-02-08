<?php declare(strict_types=1);

namespace Find\Stock\Ports;

use Find\Stock\Domain\Stock;
use Find\Stock\Domain\StockableItem;
use Find\Stock\Domain\StockableItemId;

class DefaultStockableItem implements StockableItem
{
    public function __construct(StockableItemId $stockableItemId, Stock $stock)
    {

    }

    public function stockableItemId(): StockableItemId
    {
        return $this->stockableItemId;
    }

    public function stock(): Stock
    {
        return $this->stock;
    }

    public function allowStockChanges(): bool
    {
        // TODO: Implement allowStockChanges() method.
    }

    public function purchasableStock(int $quantity): bool
    {
//        return $this->stock->
    }

    public function purchasableWhenOutOfStock(): bool
    {
        // TODO: Implement purchasableWhenOutOfStock() method.
    }
}
