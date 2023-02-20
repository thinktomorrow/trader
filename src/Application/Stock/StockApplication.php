<?php

namespace Thinktomorrow\Trader\Application\Stock;

use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Stock\StockItemRepository;
use Thinktomorrow\Trader\TraderConfig;

class StockApplication
{
    private TraderConfig $traderConfig;
    private EventDispatcher $eventDispatcher;
    private StockItemRepository $stockItemRepository;

    public function __construct(TraderConfig $traderConfig, EventDispatcher $eventDispatcher, StockItemRepository $stockItemRepository)
    {
        $this->traderConfig = $traderConfig;
        $this->eventDispatcher = $eventDispatcher;
        $this->stockItemRepository = $stockItemRepository;
    }

    public function addStock(AddStock $addStock): void
    {
        $stockItem = $this->stockItemRepository->findStockItem($addStock->getStockItemId());

        $stockItem->updateStockLevel(
            $stockItem->getStockLevel() + $addStock->getAmount()
        );

        $this->stockItemRepository->saveStockItem($stockItem);

        $this->eventDispatcher->dispatchAll($stockItem->releaseEvents());
    }

    public function reduceStock(ReduceStock $reduceStock): void
    {
        $stockItem = $this->stockItemRepository->findStockItem($reduceStock->getStockItemId());

        $stockItem->updateStockLevel(
            $stockItem->getStockLevel() - $reduceStock->getAmount()
        );

        $this->stockItemRepository->saveStockItem($stockItem);

        $this->eventDispatcher->dispatchAll($stockItem->releaseEvents());
    }
}
