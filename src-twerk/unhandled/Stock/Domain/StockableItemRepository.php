<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Stock\Domain;

interface StockableItemRepository
{
    public function findById(StockableItemId $stockableItemId): StockableItem;
}
