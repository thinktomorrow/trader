<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Stock\Domain;

interface Location
{
    public function addStock(StockableItem $stockableItem, int $quantity): void;

    public function reduceStock(StockableItem $stockableItem, int $quantity): void;

    public function replaceStock(StockableItem $stockableItem, int $quantity): void;
}