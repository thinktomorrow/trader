<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Find\Stock\Domain;

interface StockableItem
{
    public function stockableItemId(): StockableItemId;

    public function stock(): Stock;

    public function allowStockChanges(): bool;

    public function purchasableStock(int $quantity): bool;

    public function purchasableWhenOutOfStock(): bool;

//    public function sku(): string;
//    public function barcode(): string;
}
