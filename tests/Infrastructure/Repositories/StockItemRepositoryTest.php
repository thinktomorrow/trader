<?php

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Stock\Exceptions\CouldNotFindStockItem;
use Thinktomorrow\Trader\Domain\Model\Stock\StockItem;
use Thinktomorrow\Trader\Domain\Model\Stock\StockItemId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

class StockItemRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider items
     */
    public function it_can_find_and_save_a_stock_item(StockItem $stockItem)
    {
        foreach ($this->repositories() as $i => $repository) {
            $product = $this->createProductWithVariant();
            iterator_to_array($this->productRepositories())[$i]->save($product);

            $repository->saveStockItem($stockItem);

            $this->assertEquals($stockItem, $repository->findStockItem($stockItem->stockItemId));
        }
    }

    public function test_it_halts_when_stock_item_is_not_found()
    {
        $flag = 0;

        foreach ($this->repositories() as $i => $repository) {
            try {
                $repository->findStockItem(StockItemId::fromString('unknown'));
            } catch(CouldNotFindStockItem $e) {
                $flag++;
            }
        }

        $this->assertEquals(2, $flag);
    }

    private function repositories(): \Generator
    {
        yield new InMemoryVariantRepository();
        yield new MysqlVariantRepository(new TestContainer());
    }

    private function productRepositories(): \Generator
    {
        yield new InMemoryProductRepository();
        yield new MysqlProductRepository(new MysqlVariantRepository(new TestContainer()));
    }

    public function items(): \Generator
    {
        yield [
            StockItem::fromMappedData([
                'stockitem_id' => 'yyy',
                'stock_level' => 43,
                'ignore_out_of_stock' => false,
                'stock_data' => json_encode(['foo' => 'bar']),
            ]),
        ];
    }
}
