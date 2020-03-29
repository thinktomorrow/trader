<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Find\Stock\Domain;

interface StockableItemRepository
{
    public function findById(StockableItemId $stockableItemId): StockableItem;
}
