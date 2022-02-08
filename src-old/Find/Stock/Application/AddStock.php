<?php declare(strict_types=1);

namespace Find\Stock\Application;

use Find\Stock\Domain\LocationId;
use Find\Stock\Domain\StockableItemId;

class AddStock
{
    public function handle(StockableItemId $stockableItemId, LocationId $locationId, int $quantity): void
    {
        // Retrieve stockable item
        $stockableItem = $this->stockableItemRepository->findById($stockableItemId);

        // retrieve location ...
        $location = $this->locationRepository->findById($locationId);

        // Add stock to the location
        $location->addStock($stockableItem, $quantity);

        // LocationRepository save - NEEDS TO BE TRANSACTION LOCKED...
        $this->locationRepository->save($location);

        // event broadcast
        // event(new stockAdded());
    }
}
