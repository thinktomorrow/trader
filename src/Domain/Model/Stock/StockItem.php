<?php

namespace Thinktomorrow\Trader\Domain\Model\Stock;

use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Model\Stock\Events\StockAdded;
use Thinktomorrow\Trader\Domain\Model\Stock\Events\StockDepleted;
use Thinktomorrow\Trader\Domain\Model\Stock\Events\StockReduced;

class StockItem implements Aggregate
{
    use RecordsEvents;
    use HasData;

    public readonly StockItemId $stockItemId;
    private int $stockLevel;
    private bool $ignoreOutOfStock;

    public function updateStockLevel(int $level): void
    {
        $formerStockLevel = $this->stockLevel;
        $this->stockLevel = $level;

        $this->recordStockChange($formerStockLevel, $this->stockLevel);
    }

    private function recordStockChange(int $formerStockLevel, int $newStockLevel): void
    {
        $eventPayload = [
            $this->stockItemId, $formerStockLevel, $newStockLevel,
        ];

        if ($formerStockLevel < $this->stockLevel) {
            $this->recordEvent(new StockAdded(...$eventPayload));
        }

        if ($formerStockLevel > $this->stockLevel) {
            $this->recordEvent(new StockReduced(...$eventPayload));
        }

        if ($this->stockLevel <= 0) {
            $this->recordEvent(new StockDepleted(...$eventPayload));
        }
    }

    public function getStockLevel(): int
    {
        return $this->stockLevel;
    }

    public function ignoreOutOfStock(bool $ignoreOutOfStock): void
    {
        $this->ignoreOutOfStock = $ignoreOutOfStock;
    }

    public function ignoresOutOfStock(): bool
    {
        return $this->ignoreOutOfStock;
    }

    public function getMappedData(): array
    {
        return [
            'stockitem_id' => $this->stockItemId->get(),
            'stock_level' => $this->stockLevel,
            'ignore_out_of_stock' => $this->ignoreOutOfStock,
            'stock_data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $stockItem = new self();

        $stockItem->stockItemId = StockItemId::fromString($state['stockitem_id']);
        $stockItem->stockLevel = (int) $state['stock_level'];
        $stockItem->ignoreOutOfStock = (bool) $state['ignore_out_of_stock'];
        $stockItem->data = $state['stock_data'] ? json_decode($state['stock_data'], true) : [];

        return $stockItem;
    }
}
