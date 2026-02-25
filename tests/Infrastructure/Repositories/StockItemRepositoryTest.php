<?php

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Stock\Exceptions\CouldNotFindStockItem;
use Thinktomorrow\Trader\Domain\Model\Stock\Exceptions\VariantRecordDoesNotExistWhenSavingStockItem;
use Thinktomorrow\Trader\Domain\Model\Stock\StockItemId;
use Thinktomorrow\Trader\Testing\Catalog\CatalogContext;

class StockItemRepositoryTest extends TestCase
{
    public function test_it_can_find_and_save_a_stock_item()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            $repository = $catalog->repos()->stockItemRepository();

            $catalog->createProduct();
            $stockItem = $catalog->dontPersist()->createStockItem();

            $repository->saveStockItem($stockItem);

            $this->assertEquals($stockItem, $repository->findStockItem($stockItem->stockItemId));
        }
    }

    public function test_it_throws_exception_when_variant_does_not_exist()
    {
        $flag = 0;

        foreach (CatalogContext::drivers() as $catalog) {

            try {
                $catalog->createStockItem();
            } catch (VariantRecordDoesNotExistWhenSavingStockItem $e) {
                $flag++;
            }
        }

        $this->assertEquals(count(CatalogContext::drivers()), $flag);
    }

    public function test_it_halts_when_stock_item_is_not_found()
    {
        $flag = 0;

        foreach (CatalogContext::drivers() as $catalog) {

            $repository = $catalog->repos()->stockItemRepository();

            try {
                $repository->findStockItem(StockItemId::fromString('unknown'));
            } catch (CouldNotFindStockItem $e) {
                $flag++;
            }
        }

        $this->assertEquals(count(CatalogContext::drivers()), $flag);
    }
}
