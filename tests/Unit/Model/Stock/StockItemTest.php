<?php

namespace Tests\Unit\Model\Stock;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Model\Stock\Events\StockAdded;
use Thinktomorrow\Trader\Domain\Model\Stock\Events\StockDepleted;
use Thinktomorrow\Trader\Domain\Model\Stock\Events\StockReduced;
use Thinktomorrow\Trader\Domain\Model\Stock\StockItem;

class StockItemTest extends TestCase
{
    public function test_it_can_create_a_stock_item()
    {
        $item = $this->getStockItem();

        $this->assertEquals([
            'stockitem_id' => 'xxx',
            'stock_level' => 5,
            'ignore_out_of_stock' => false,
            'stock_data' => json_encode([]),
        ], $item->getMappedData());
    }

    public function test_it_can_update_a_stock_level()
    {
        $item = $this->getStockItem();
        $item->updateStockLevel(10);

        $this->assertEquals(10, $item->getStockLevel());
        $this->assertEquals(10, $item->getMappedData()['stock_level']);
    }

    public function test_it_can_update_ignore_out_of_stock_setting()
    {
        $item = $this->getStockItem();

        $item->ignoreOutOfStock(true);
        $this->assertEquals(true, $item->ignoresOutOfStock());
        $this->assertEquals(true, $item->getMappedData()['ignore_out_of_stock']);
    }

    public function test_it_releases_stock_added_event()
    {
        $item = $this->getStockItem();
        $item->updateStockLevel(10);

        $this->assertEquals([
           new StockAdded($item->stockItemId, 5, 10),
        ], $item->releaseEvents());
    }

    public function test_it_releases_stock_reduced_event()
    {
        $item = $this->getStockItem();
        $item->updateStockLevel(3);

        $this->assertEquals([
            new StockReduced($item->stockItemId, 5, 3),
        ], $item->releaseEvents());
    }

    public function test_it_releases_stock_depleted_event_when_stock_is_zero()
    {
        $item = $this->getStockItem();
        $item->updateStockLevel(0);

        $this->assertEquals([
            new StockReduced($item->stockItemId, 5, 0),
            new StockDepleted($item->stockItemId, 5, 0),
        ], $item->releaseEvents());
    }

    public function test_it_releases_stock_depleted_event_when_stock_is_negative()
    {
        $item = $this->getStockItem();
        $item->updateStockLevel(-3);

        $this->assertEquals([
            new StockReduced($item->stockItemId, 5, -3),
            new StockDepleted($item->stockItemId, 5, -3),
        ], $item->releaseEvents());
    }

    public function test_it_can_update_stock_item_data()
    {
        $item = $this->getStockItem();

        $item->addData(['foo' => 'bar']);

        $this->assertEquals(json_encode(['foo' => 'bar']), $item->getMappedData()['stock_data']);
    }

    private function getStockItem()
    {
        return StockItem::fromMappedData([
            'stockitem_id' => 'xxx',
            'stock_level' => 5,
            'ignore_out_of_stock' => false,
            'stock_data' => json_encode([]),
        ]);
    }
}
