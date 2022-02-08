<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Stock\Ports;

use Thinktomorrow\Trader\Stock\Domain\Stock;
use Thinktomorrow\Trader\Stock\Domain\StockableItem;
use Thinktomorrow\Trader\Stock\Domain\StockableItemId;

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
