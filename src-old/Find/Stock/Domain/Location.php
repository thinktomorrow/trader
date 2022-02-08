<?php declare(strict_types=1);

namespace Find\Stock\Domain;

interface Location
{
    public function addStock(StockableItem $stockableItem, int $quantity): void;

    public function reduceStock(StockableItem $stockableItem, int $quantity): void;

    public function replaceStock(StockableItem $stockableItem, int $quantity): void;
}
